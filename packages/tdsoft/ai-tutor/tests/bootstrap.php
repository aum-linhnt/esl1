<?php

require dirname(__DIR__, 4).'/vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'TDSoft\\AiTutor\\Tests\\';
    if (str_starts_with($class, $prefix)) {
        require __DIR__.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    }
});
