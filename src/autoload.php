<?php

spl_autoload_register(function ($className) {
    $baseDir = __DIR__ . '/';

    $prefix = 'App\\';
    $prefixLength = strlen($prefix);

    if (strncmp($prefix, $className, $prefixLength) !== 0) {
        return;
    }

    $relativeClass = substr($className, $prefixLength);

    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
