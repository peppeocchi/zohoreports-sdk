<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ZohoReports\ZohoReports;

$zoho = new ZohoReports('my-zoho-email', 'my-db', 'my-authtoken');

try {
    $res = $zoho->import('somefile.csv', 'My-Table');
} catch (\Exception $e) {
    echo $e->getMessage();
    exit();
}

var_dump($res);
