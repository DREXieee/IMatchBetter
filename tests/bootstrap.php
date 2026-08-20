<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/src/Helpers/functions.php';

// Tests always run against a dedicated `imatchbetter_test` database — never the dev/prod one.
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH, '.env.testing');
$dotenv->load();

if (($_ENV['DB_NAME'] ?? '') === '' || !str_ends_with((string) $_ENV['DB_NAME'], '_test')) {
    fwrite(STDERR, "Refusing to run tests: DB_NAME must point at a *_test database (see .env.testing).\n");
    exit(1);
}
