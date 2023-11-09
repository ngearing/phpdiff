<?php

require_once __DIR__ . '/vendor/autoload.php';

try {
    $phpdiff = new NG\PHPDiff\PHPDiff();
} catch( Exception $e ) {
    echo 'Error: ',  $e->getMessage(), "\n";
    exit;
}
