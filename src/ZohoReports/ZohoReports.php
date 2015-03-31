<?php namespace ZohoReports;

class ZohoReports
{

  /**
   * URI
   */
  private $uri = 'https://reportsapi.zoho.com/api/';

  /**
   * Api version
   */
  private $api = '1.0';

  /**
   * Zoho Login Email Address
   */
  protected $email;

  /**
   * Zoho Authtoken
   */
  protected $authtoken;

  /**
   * Database Name
   */
  protected $dbName;

  /**
   * Output format
   *
   * values: xml, json
   */
  protected $outputFormat = 'JSON';

  /**
   * Errors format
   *
   * values: xml, json
   */
  protected $errorsFormat = 'JSON';

  /**
   * Zoho actions
   */
  protected $actions = [
    'addrow'     => 'ADDROW',
    'import'     => 'IMPORT',
    'update'     => 'UPDATE',
    'delete'     => 'DELETE',
    'export'     => 'EXPORT',
    'dbmetadata' => 'DATABASEMETADATA'
  ];


  /**
   * Init
   *
   * @param [string] $email - zoho login email address
   * @param [string] $dbName - database name
   *
   */
  public function __construct($email, $dbName, $authtoken)
  {
    $this->email = $email;
    $this->dbName = $dbName;
    $this->authtoken = $authtoken;
  }

  /**
   * Import CSV
   *
   * @param [string] $file - path to file
   * @param [string] $table - table name
   * @param [array] $params - parameters to be added
   *
   */
  public function import($file, $table, $params = [])
  {
    $this->action = $this->actions['import'];
    $this->table = $table;

    $uri = $this->buildUri();

    $post = $this->setImportParams($params);

    if(!file_exists($file)) {
      throw new InvalidFileException("The file provided does not exist. {$file}");
    }

    $post['ZOHO_FILE'] = new \CURLFile(realpath($file), 'text/csv', basename($file));

    return $this->request($uri, $post);
  }

  /**
   * Set import parameters
   *
   * @param [array] $params
   *
   * @return [array]
   */
  private function setImportParams($params)
  {
    $default = [
      'format'       => 'CSV',
      'create'       => 'true',
      'type'         => 'TRUNCATEADD',
      'dateFormat'   => 'yyyy-MM-dd HH:mm:ss',
      'autoIdentify' => 'true',
      'skip'         => 0,
      'onError'      => 'SETCOLUMNEMPTY'
    ];

    $post = [];

    $post['ZOHO_IMPORT_FILETYPE'] = isset($params['format']) ? strtoupper($params['format']) : $default['format'];
    $post['ZOHO_CREATE_TABLE'] = isset($params['create']) ? (bool)$params['create'] : $default['create'];
    $post['ZOHO_IMPORT_TYPE'] = isset($params['type']) ? strtoupper($params['type']) : $default['type'];

    if($post['ZOHO_IMPORT_TYPE'] === 'UPDATEADD') {
      if(!isset($params['pk'])) {
        throw new InvalidParamsException('When using UPDATEADD you must provide a primary key.');
      }

      $post['ZOHO_MATCHING_COLUMNS'] = $params['pk'];
    }

    $post['ZOHO_DATE_FORMAT'] = isset($params['dateFormat']) ? $params['dateFormat'] : $default['dateFormat'];

    if($post['ZOHO_IMPORT_FILETYPE'] === 'CSV') {
      $post['ZOHO_AUTO_IDENTIFY'] = isset($params['autoIdentify']) ? (bool)$params['autoIdentify'] : $default['autoIdentify'];
      $post['ZOHO_SKIPTOP'] = isset($params['skip']) ? $params['skip'] : $default['skip'];

      if($post['ZOHO_AUTO_IDENTIFY'] === false) {
        if(!isset($params['csvCommentChar'])) {
          throw new InvalidParamsException('"csvCommentChars" is required if "autoIdentify" is false.');
        }
        if(!isset($params['csvDelimeter'])) {
          throw new InvalidParamsException('"csvCommentChars" is required if "autoIdentify" is false.');
        }
        if(!isset($params['csvQuoted'])) {
          throw new InvalidParamsException('"csvCommentChars" is required if "autoIdentify" is false.');
        }

        $post['ZOHO_COMMENTCHAR'] = $params['csvCommentChar'];
        $post['ZOHO_DELIMITER'] = $params['csvDelimeter'];
        $post['ZOHO_QUOTED'] = $params['csvQuoted'];
      }
    }

    $post['ZOHO_ON_IMPORT_ERROR'] = isset($params['onError']) ? $params['onError'] : $default['onError'];

    return $post;
  }

  /**
   * Build uri
   *
   */
  private function buildUri()
  {
    $uri = $this->uri
         . $this->email . '/'
         . $this->dbName . '/'
         . $this->table
         . "?ZOHO_ACTION={$this->action}"
         . "&ZOHO_OUTPUT_FORMAT={$this->outputFormat}"
         . "&ZOHO_ERROR_FORMAT={$this->errorsFormat}"
         . "&ZOHO_API_VERSION={$this->api}"
         . "&authtoken={$this->authtoken}";

    return $uri;
  }

  /**
   * Query the zoho reports api
   *
   */
  private function request($uri, $post = false)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if(is_array($post)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }

    $result = curl_exec($ch);
    $info = curl_getinfo($ch);

    curl_close($ch);

    return $result;
  }
}


class InvalidFileException extends \Exception {}
class InvalidParamsException extends \Exception {}
