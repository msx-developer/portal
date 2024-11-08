<?php

$host = $dbname = $user = $password = $type = $port = $portal = null;

if(function_exists('getenv')) {
    $host = getenv('DB_MSX_HOST');
    $dbname = getenv('DB_MSX_DATABASE');
    $user = getenv('DB_MSX_USERNAME');
    $password = getenv('DB_MSX_PASSWORD');
    $type = getenv('DB_MSX_CONNECTION');
    $portal = getenv('DB_MSX_PORTAL');

    if((!isset($_SESSION['msx']['portal']) || $_SESSION['portal'] != $portal) && $portal > 0) {
        session_start();
        $_SESSION['msx']['portal'] = $portal;
    }
}

return [
    'database' => [
        'host' => $host ?? 'localhost',
        'dbname' => $dbname ?? 'portal',
        'user' => $user,
        'password' => $password,
        'type' => $type ?? 'mysql',
        'port' => $port ?? '3306'
    ],
    'portal' => $portal
];