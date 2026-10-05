<?php

// Docker's existing $_SERVER variables can override PHPUnit's <env force="true">.
// Set every environment source before Laravel boots so tests cannot use the host DB.
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'DB_URL' => ''] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
require dirname(__DIR__).'/vendor/autoload.php';
