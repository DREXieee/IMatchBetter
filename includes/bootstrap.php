<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/src/Helpers/functions.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

error_reporting(E_ALL);
ini_set('display_errors', ($_ENV['APP_ENV'] ?? 'local') === 'local' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/logs/app.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}
