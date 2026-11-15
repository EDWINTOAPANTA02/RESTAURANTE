<?php
/**
 * Plantilla de configuración de entorno (copiar a config.php y ajustar en producción).
 */
return [
    'env' => getenv('APP_ENV') ?: 'local', // Cambiar a 'production' en el servidor real
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'botanero_ventas',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    // Lista blanca de orígenes permitidos para CORS con credenciales
    'allowed_origins' => [
        'http://localhost',
        'http://localhost:8080',
        'http://127.0.0.1',
        // 'https://tu-subdominio.infinityfreeapp.com',
    ],
    'paypal' => [
        'client_id' => getenv('PAYPAL_CLIENT_ID') ?: 'TU_PAYPAL_CLIENT_ID',
        'secret' => getenv('PAYPAL_SECRET') ?: 'TU_PAYPAL_SECRET',
        'api_url' => getenv('PAYPAL_API_URL') ?: 'https://api-m.paypal.com', // Usar https://api-m.sandbox.paypal.com en pruebas
    ],
];
