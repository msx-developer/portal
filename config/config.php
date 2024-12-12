<?php 

$host = $dbname = $user = $password = $type = $port = $portal =  $url_admin = null;

if(function_exists('getenv')) {
    $host = getenv('DB_MSX_HOST');
    $dbname = getenv('DB_MSX_DATABASE');
    $user = getenv('DB_MSX_USERNAME');
    $password = getenv('DB_MSX_PASSWORD');
    $type = getenv('DB_MSX_CONNECTION');
    $portal = getenv('DB_MSX_PORTAL');
    $url_admin = getenv('DB_MSX_URL_ADMIN');

    $elasticsearch_host = getenv('DB_MSX_ELASTICSEARCH_HOST');
    $elasticsearch_port = getenv('DB_MSX_ELASTICSEARCH_PORT');
    $elasticsearch_pass = getenv('DB_MSX_ELASTICSEARCH_PASSWORD');
    $elasticsearch_user = getenv('DB_MSX_ELASTICSEARCH_USER');
    $elasticsearch_prefix = getenv('DB_MSX_ELASTICSEARCH_PREFIX');

    if((!isset($_SESSION['msx']['portal']) || $_SESSION['portal'] != $portal) && $portal > 0) {
        session_start();
        $_SESSION['msx']['portal'] = $portal;
        $_SESSION['msx']['url_admin'] = $url_admin;
    }
}

return [
    'database' => [
        'host' => $host ?? 'localhost',
        'dbname' => $dbname ?? 'portal',
        'user' => $user,
        'password' => $password,
        'type' => $type ?? 'mysql',
        'port' => $port ?? '3306',
        'charset' => 'utf8'
    ],
    'elasticsearch' => [
        'host' => $elasticsearch_host ?? 'localhost',
        'port' => $elasticsearch_port ?? '9200',
        'user' => $elasticsearch_user,
        'password' => $elasticsearch_pass,
        'prefix' => $elasticsearch_prefix
    ],
    'portal' => $portal,
    'url_admin' => $url_admin
];