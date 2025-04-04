<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Force test environment
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_DEBUG'] = '1';
$_SERVER['APP_DEBUG'] = '1';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
}

// Load test environment
if (class_exists(Dotenv::class)) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env.test');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
