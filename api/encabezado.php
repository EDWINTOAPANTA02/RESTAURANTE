<?php
$configPath = __DIR__ . '/config.php';
$config = file_exists($configPath) ? require $configPath : require __DIR__ . '/config.example.php';
$allowedOrigins = $config['allowed_origins'] ?? ['http://localhost', 'http://localhost:8080'];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS requests
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
