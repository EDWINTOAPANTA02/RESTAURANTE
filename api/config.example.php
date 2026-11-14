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
];
