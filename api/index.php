<?php

if (isset($_GET['check'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "AKSARA PHP Runner OK!\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "PostgreSQL Extension: " . (extension_loaded('pdo_pgsql') ? 'TERPASANG (OK)' : 'TIDAK ADA') . "\n";
    exit(0);
}

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
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

    if (empty($_SERVER['REMOTE_ADDR'])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '127.0.0.1';
    }

    $runtimePaths = [
        'LARAVEL_STORAGE_PATH' => $storage,
        'VIEW_COMPILED_PATH' => $storage.'/framework/views',
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
    // Detail teknis hanya dicatat ke log server, tidak ditampilkan ke pengunjung.
    error_log('AKSARA boot error: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="font-family:sans-serif;padding:30px;max-width:640px;margin:auto;">';
    echo '<h2>Halaman sedang tidak dapat dibuka</h2>';
    echo '<p>Silakan coba lagi beberapa saat lagi atau hubungi admin sekolah.</p>';
    echo '</div>';
    exit(0);
}
