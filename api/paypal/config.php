<?php
// Configuración de PayPal (cargada desde api/config.php o variables de entorno)
$_appConfig = file_exists(__DIR__ . '/../config.php')
    ? require __DIR__ . '/../config.php'
    : (file_exists(__DIR__ . '/../config.example.php') ? require __DIR__ . '/../config.example.php' : []);

$_paypalConfig = $_appConfig['paypal'] ?? [];
$_rawClientId = getenv('PAYPAL_CLIENT_ID') ?: ($_paypalConfig['client_id'] ?? 'sb');
// Si se usa el alias 'sb' de Sandbox, usar el Client ID oficial de Sandbox para REST API
$_resolvedClientId = ($_rawClientId === 'sb' || $_rawClientId === 'sandbox')
    ? 'AZDxjDScFpQtjWTOUtWKbyN_bDt4OgqaF4eYXlewfBP4-8aqX3PiV8e1GWU6liB2CUXlkA59kJXE7M6R'
    : $_rawClientId;

define('PAYPAL_SDK_CLIENT_ID', $_rawClientId);
define('PAYPAL_CLIENT_ID', $_resolvedClientId);
define('PAYPAL_SECRET', getenv('PAYPAL_SECRET') ?: ($_paypalConfig['secret'] ?? ''));
define('PAYPAL_API_URL', getenv('PAYPAL_API_URL') ?: ($_paypalConfig['api_url'] ?? 'https://api-m.sandbox.paypal.com'));

$_appEnv = $_appConfig['env'] ?? (getenv('APP_ENV') ?: 'local');
define('PAYPAL_VERIFY_SSL', $_appEnv === 'production');

function obtenerTokenPayPal() {
    $credenciales = [
        PAYPAL_CLIENT_ID . ":" . PAYPAL_SECRET,
        PAYPAL_CLIENT_ID . ":", // Fallback client-only token si el secret fue rotado
    ];

    foreach ($credenciales as $userPwd) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . "/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, PAYPAL_VERIFY_SSL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $userPwd);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

        $result = curl_exec($ch);
        curl_close($ch);
        if (!empty($result)) {
            $json = json_decode($result);
            if (!empty($json->access_token)) {
                return $json->access_token;
            }
        }
    }
    return null;
}
