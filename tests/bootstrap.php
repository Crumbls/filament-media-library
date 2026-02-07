<?php

require_once __DIR__.'/../../../vendor/autoload.php';

// Register the package's test namespace since autoload-dev isn't loaded by the host app
spl_autoload_register(function (string $class): void {
    $prefix = 'Crumbls\\FilamentMediaLibrary\\Tests\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__.'/'.str_replace('\\', '/', $relativeClass).'.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
