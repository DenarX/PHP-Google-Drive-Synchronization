<?php
// composer require google/apiclient:^2.11
require_once 'vendor/autoload.php';
require_once 'sync.php';
try {
    if (!file_exists('config.php'))  throw new Exception("Rename config.php.example to config.php and set your googleDriveFolderId");
    $config = include 'config.php';
    new sync($config['syncFolder'], $config['googleDriveFolderId']);
} catch (\Throwable $t) {
    sync::getError($t);
}
header('Content-Type: application/json; charset=utf-8');
print_r(sync::result(true));
