<?php
// Configuración de PayPal (cargada desde api/config.php o variables de entorno)
$_appConfig = file_exists(__DIR__ . '/../config.php')
    ? require __DIR__ . '/../config.php'
    : (file_exists(__DIR__ . '/../config.example.php') ? require __DIR__ . '/../config.example.php' : []);

$_paypalConfig = $_appConfig['paypal'] ?? [];

define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: ($_paypalConfig['client_id'] ?? 'AbQXCIegm1AoyWZom0PiI3z5z_6UobxriqJj49KJLWGYdfIwT8TjQsDqTaaDfg8M3BoogmPVHiTpe8lu'));
define('PAYPAL_SECRET', getenv('PAYPAL_SECRET') ?: ($_paypalConfig['secret'] ?? ''));
define('PAYPAL_API_URL', getenv('PAYPAL_API_URL') ?: ($_paypalConfig['api_url'] ?? 'https://api-m.paypal.com'));

function obtenerTokenPayPal() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . "/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

    $result = curl_exec($ch);
    if (empty($result)) {
        curl_close($ch);
        return null;
    } else {
        $json = json_decode($result);
        curl_close($ch);
        return $json->access_token ?? null;
    }
}
