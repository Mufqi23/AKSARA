<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    // Vercel invokes this function for dynamic requests. Keep Laravel's public
    // front controller as the single HTTP entry point.
    $storage = sys_get_temp_dir().'/aksara';

    foreach (['app/private', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
        if (! is_dir($storage.'/'.$directory) && ! @mkdir($storage.'/'.$directory, 0775, true) && ! is_dir($storage.'/'.$directory)) {
            throw new RuntimeException('Unable to prepare temporary Laravel storage.');
        }
    }

    $runtimePaths = [
        'LARAVEL_STORAGE_PATH' => $storage,
        'VIEW_COMPILED_PATH' => $storage.'/framework/views',
        'APP_CONFIG_CACHE' => $storage.'/framework/cache/config.php',
        'APP_EVENTS_CACHE' => $storage.'/framework/cache/events.php',
        'APP_PACKAGES_CACHE' => $storage.'/framework/cache/packages.php',
        'APP_ROUTES_CACHE' => $storage.'/framework/cache/routes-v7.php',
        'APP_SERVICES_CACHE' => $storage.'/framework/cache/services.php',
    ];

    foreach ([
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'LOG_CHANNEL' => 'stderr',
        'LOG_LEVEL' => 'warning',
        'SESSION_DRIVER' => 'cookie',
        'CACHE_STORE' => 'array',
        'SESSION_SECURE_COOKIE' => 'true',
        'SESSION_ENCRYPT' => 'true',
    ] as $name => $value) {
        if (getenv($name) === false && ! isset($_ENV[$name]) && ! isset($_SERVER[$name])) {
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv($name.'='.$value);
        }
    }

    foreach ($runtimePaths as $name => $path) {
        $_ENV[$name] = $path;
        $_SERVER[$name] = $path;
        putenv($name.'='.$path);
    }

    // Marks requests handled behind Vercel's HTTPS ingress for Laravel's proxy middleware.
    $_ENV['LMS_VERCEL_RUNTIME'] = $_SERVER['LMS_VERCEL_RUNTIME'] = 'true';
    putenv('LMS_VERCEL_RUNTIME=true');

    if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        echo "<h2>Status Deployment AKSARA</h2>";
        echo "<p>Dependensi composer (folder <code>vendor/</code>) belum terpasang di Vercel.</p>";
        exit(0);
    }

    require __DIR__.'/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    echo "<div style='font-family:sans-serif;padding:30px;max-width:800px;margin:auto;'>";
    echo "<h2 style='color:#dc2626;'>Pemberitahuan Sistem AKSARA</h2>";
    echo "<p><strong>Pesan:</strong> ".htmlspecialchars($e->getMessage())."</p>";
    echo "<p><strong>Lokasi:</strong> ".htmlspecialchars($e->getFile().":".$e->getLine())."</p>";
    echo "<details><summary style='cursor:pointer;'>Lihat Stack Trace Lengkap</summary><pre style='background:#f3f4f6;padding:12px;border-radius:8px;overflow:auto;'>".htmlspecialchars($e->getTraceAsString())."</pre></details>";
    echo "</div>";
    exit(0);
}
