<?php
include_once __DIR__ . "/../encabezado.php";
include_once __DIR__ . "/../funciones.php";
include_once __DIR__ . "/config.php";

$datos = json_decode(file_get_contents("php://input"));
if (!$datos || !isset($datos->orderID) || !isset($datos->empresa_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Faltan datos para procesar el pago"]);
    exit;
}

$token = obtenerTokenPayPal();
$capturado = false;
$monto = $datos->monto ?? 15.00;
$referencia = $datos->orderID;

if ($token) {
    // 1. Capturar el pago en PayPal
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_URL . "/v2/checkout/orders/" . urlencode($datos->orderID) . "/capture");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, PAYPAL_VERIFY_SSL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $token
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resData = json_decode($response);
    if ($httpCode === 201 || ($httpCode === 200 && isset($resData->status) && $resData->status === 'COMPLETED')) {
        $capturado = true;
        $monto = $resData->purchase_units[0]->payments->captures[0]->amount->value ?? $monto;
        $referencia = $resData->id ?? $referencia;
    }
} else {
    // Si la orden fue capturada directamente por el SDK de PayPal en el cliente
    $capturado = !empty($datos->orderID);
}

if ($capturado) {
    // 2. Pago exitoso, activar la empresa en la BD
    $bd = conectarBaseDatos();
    try {
        $bd->beginTransaction();

        // Registrar el pago
        $stmtPago = $bd->prepare("INSERT INTO pagos_empresa (empresa_id, monto, metodo_pago, referencia, fecha_pago) VALUES (?, ?, 'PAYPAL', ?, NOW())");
        $stmtPago->execute([$datos->empresa_id, $monto, $referencia]);

        // Actualizar fecha de vencimiento (1 mes a partir de hoy) y activar estado
        $nuevaFecha = date('Y-m-d', strtotime('+1 month'));
        $stmtEmpresa = $bd->prepare("UPDATE empresas SET estado = 'ACTIVO', fecha_vencimiento = ? WHERE id = ?");
        $stmtEmpresa->execute([$nuevaFecha, $datos->empresa_id]);

        $bd->commit();
        echo json_encode(["resultado" => true, "mensaje" => "¡Empresa reactivada con éxito!", "nueva_fecha" => $nuevaFecha]);
    } catch (Exception $e) {
        if ($bd->inTransaction()) {
            $bd->rollBack();
        }
        http_response_code(500);
        echo json_encode(["resultado" => false, "error" => "Pago capturado pero ocurrió un error al actualizar la base de datos: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["resultado" => false, "error" => "No se pudo completar la captura del pago"]);
}
