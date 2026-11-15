<?php
include_once __DIR__ . "/../encabezado.php";
include_once __DIR__ . "/../funciones.php";
include_once __DIR__ . "/config.php";

$datos = json_decode(file_get_contents("php://input"));
if (!$datos || empty($datos->orderID) || empty($datos->empresa_id)) {
    http_response_code(400);
    echo json_encode(["resultado" => false, "error" => "Faltan datos para procesar el pago"]);
    exit;
}

$token = obtenerTokenPayPal();
$capturado = false;
$monto = isset($datos->monto) ? (float)$datos->monto : 15.00;
$idTransaccion = $datos->orderID;

if ($token) {
    // 1. Intentar capturar el pago en PayPal
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
        $idTransaccion = $resData->id ?? $idTransaccion;
    } else {
        // Si ya fue capturada (ORDER_ALREADY_CAPTURED), consultar el estado de la orden con GET
        $chGet = curl_init();
        curl_setopt($chGet, CURLOPT_URL, PAYPAL_API_URL . "/v2/checkout/orders/" . urlencode($datos->orderID));
        curl_setopt($chGet, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chGet, CURLOPT_SSL_VERIFYPEER, PAYPAL_VERIFY_SSL);
        curl_setopt($chGet, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $token
        ]);
        $resGet = curl_exec($chGet);
        curl_close($chGet);
        $orderInfo = json_decode($resGet);

        if (isset($orderInfo->status) && ($orderInfo->status === 'COMPLETED' || $orderInfo->status === 'APPROVED')) {
            $capturado = true;
            $monto = $orderInfo->purchase_units[0]->amount->value ?? $monto;
            $idTransaccion = $orderInfo->id ?? $idTransaccion;
        }
    }
} else {
    $capturado = !empty($datos->orderID);
}

if ($capturado) {
    $bd = conectarBaseDatos();
    try {
        $bd->beginTransaction();

        // 1. Obtener fecha de vencimiento actual
        $stmtEmp = $bd->prepare("SELECT fecha_vencimiento FROM empresas WHERE id = ? FOR UPDATE");
        $stmtEmp->execute([$datos->empresa_id]);
        $empresa = $stmtEmp->fetchObject();

        if (!$empresa) {
            throw new Exception("Empresa no encontrada");
        }

        // 2. Calcular nueva fecha (1 mes adicional desde hoy o desde vencimiento futuro)
        $hoy = date('Y-m-d');
        $baseFecha = ($empresa->fecha_vencimiento && $empresa->fecha_vencimiento > $hoy) ? $empresa->fecha_vencimiento : $hoy;
        $nuevaFechaVencimiento = date('Y-m-d', strtotime($baseFecha . ' + 1 month'));

        // 3. Registrar el pago en el historial (pagos_empresa)
        $stmtPago = $bd->prepare("INSERT INTO pagos_empresa (empresa_id, monto, metodo_pago, mes_pagado, fecha_vencimiento_nueva, notas) VALUES (?,?,?,?,?,?)");
        $stmtPago->execute([
            $datos->empresa_id,
            $monto,
            'PAYPAL',
            date('Y-m'),
            $nuevaFechaVencimiento,
            "Pago Digital PayPal ID: $idTransaccion"
        ]);

        // 4. Actualizar estado de la empresa a ACTIVO y su nueva fecha de vencimiento
        $stmtUpdate = $bd->prepare("UPDATE empresas SET fecha_vencimiento = ?, estado = 'ACTIVO' WHERE id = ?");
        $stmtUpdate->execute([$nuevaFechaVencimiento, $datos->empresa_id]);

        $bd->commit();
        echo json_encode([
            "resultado" => true,
            "mensaje" => "¡Empresa reactivada con éxito!",
            "nueva_fecha" => $nuevaFechaVencimiento
        ]);
    } catch (Exception $e) {
        if ($bd->inTransaction()) {
            $bd->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            "resultado" => false,
            "error" => "Pago capturado pero ocurrió un error al actualizar la base de datos: " . $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "resultado" => false,
        "error" => "No se pudo completar la captura del pago",
        "detalles" => $resData ?? null
    ]);
}
