<?php
include_once __DIR__ . "/../encabezado.php";
include_once __DIR__ . "/../funciones.php";
include_once __DIR__ . "/../funciones_planes.php";
include_once __DIR__ . "/config.php";

$datos = json_decode(file_get_contents("php://input"));
if (!$datos || !isset($datos->plan)) {
    http_response_code(400);
    echo json_encode(["error" => "Plan no especificado"]);
    exit;
}

// Obtener el precio real desde la base de datos para mayor seguridad
$monto = obtenerPrecioPlan($datos->plan);

$token = obtenerTokenPayPal();
if (!$token) {
    // Retornar 200 con fallback:true para que el SDK de PayPal cree la orden directamente sin error 500
    echo json_encode(["fallback" => true, "monto" => number_format($monto, 2, '.', '')]);
    exit;
}

$ch = curl_init();
$orden = [
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "amount" => [
            "currency_code" => "USD",
            "value" => number_format($monto, 2, '.', '')
        ],
        "description" => $datos->descripcion ?? "Suscripción FoodSys - Plan " . $datos->plan
    ]]
];

curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . "/v2/checkout/orders");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orden));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resDecoded = json_decode($response);

if ($httpCode !== 201 && $httpCode !== 200) {
    $issue = $resDecoded->details[0]->issue ?? ($resDecoded->name ?? 'ERROR');
    http_response_code($httpCode ?: 500);
    echo json_encode([
        "error" => "PayPal rechazó la orden ($issue)",
        "issue" => $issue,
        "debug" => $resDecoded
    ]);
    exit;
}

http_response_code($httpCode);
echo $response;
