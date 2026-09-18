<?php

use App\Support\Env;

require_once __DIR__ . '/../src/Support/Env.php';
Env::load(__DIR__ . '/../.env');

return [
    'app_env' => Env::get('APP_ENV', 'local'),
    'base_domain' => Env::get('BASE_DOMAIN', 'vendedorsincero.pro'),
    'default_workspace_slug' => Env::get('DEFAULT_WORKSPACE_SLUG', 'sincero'),
    'super_admin_token' => Env::get('SUPER_ADMIN_TOKEN', ''),
    'enforce_api_token' => Env::get('ENFORCE_API_TOKEN', false),

    'db' => [
        'driver' => Env::get('DB_DRIVER', 'sqlite'), // 'sqlite' or 'mysql'
        'sqlite_path' => (function() {
            $raw = Env::get('DB_SQLITE_PATH', 'storage/database.sqlite');
            if (str_starts_with($raw, '/') || preg_match('/^[a-zA-Z]:[\\\\\/]/', $raw)) {
                return $raw;
            }
            return dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim($raw, '/\\');
        })(),
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => (int) Env::get('DB_PORT', 3306),
        'database' => Env::get('DB_DATABASE', 'upscale_db'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],

    'pix' => [
        'expiry_minutes' => (int) Env::get('PIX_EXPIRY_MINUTES', 15),
        'mock_autoconfirm_seconds' => (int) Env::get('MOCK_PIX_AUTOCONFIRM_SECONDS', 25),
        'sandbox_pix_key' => Env::get('SANDBOX_PIX_KEY', '00000000000'),
        'sandbox_merchant_name' => Env::get('SANDBOX_MERCHANT_NAME', 'UPSCALE SANDBOX'),
        'sandbox_merchant_city' => Env::get('SANDBOX_MERCHANT_CITY', 'SAO PAULO'),
    ]
];
