<?php
/**
 * API para Gestión de Suscripciones / Licencias SaaS y Pagos (PayPal + Manual SUPERADMIN)
 */

include_once "encabezado.php";
include_once "funciones.php";
include_once "middleware/Auth.php";
include_once "middleware/Rbac.php";

$usuario = requireAuth();
$bd = conectarBaseDatos();
$datos = json_decode(file_get_contents("php://input"));
$accion = $datos->accion ?? '';

$preciosPlanes = [
    'BASICO' => 15.00,
    'PRO' => 30.00,
    'PREMIUM' => 50.00,
];

// 1. CONSULTAR ESTADO DE LICENCIA DE LA EMPRESA EN SESIÓN
if ($accion === 'estado_licencia') {
    $eid = (int)($usuario->empresa_id ?? 0);
    $stmtEmp = $bd->prepare("SELECT id, razon_social, ruc, plan, estado, fecha_vencimiento FROM empresas WHERE id = ? LIMIT 1");
    $stmtEmp->execute([$eid]);
    $emp = $stmtEmp->fetchObject();

    $stmtPagos = $bd->prepare("SELECT id, monto, metodo_pago, mes_pagado, fecha_vencimiento_nueva, notas, fecha_pago FROM pagos_empresa WHERE empresa_id = ? ORDER BY fecha_pago DESC LIMIT 10");
    $stmtPagos->execute([$eid]);
    $pagos = $stmtPagos->fetchAll();

    $hoy = date('Y-m-d');
    $expirado = ($emp && !empty($emp->fecha_vencimiento) && $emp->fecha_vencimiento < $hoy)
        || ($emp && $emp->estado !== 'ACTIVO');

    $configPath = __DIR__ . '/config.php';
    $config = file_exists($configPath) ? require $configPath : require __DIR__ . '/config.example.php';
    $paypalClientId = $config['paypal_client_id'] ?? 'sb';

    echo json_encode([
        'resultado' => true,
        'empresa' => $emp,
        'expirado' => $expirado,
        'pagos' => $pagos,
        'planes' => $preciosPlanes,
        'paypal_client_id' => $paypalClientId
    ]);
    exit;
}

// 2. RENOVAR / COMPRAR LICENCIA VÍA PAYPAL (DESDE EL RESTAURANTE)
if ($accion === 'renovar_paypal') {
    $eid = (int)($usuario->empresa_id ?? 0);
    $planElegido = strtoupper(trim($datos->plan ?? 'PRO'));
    if (!isset($preciosPlanes[$planElegido])) {
        $planElegido = 'PRO';
    }
    $monto = $preciosPlanes[$planElegido];
    $orderId = trim($datos->paypal_order_id ?? ('PP-' . strtoupper(bin2hex(random_bytes(4)))));
    $payerEmail = trim($datos->payer_email ?? ($usuario->correo ?? ''));

    try {
        $bd->beginTransaction();

        $stmtEmp = $bd->prepare("SELECT fecha_vencimiento FROM empresas WHERE id = ? FOR UPDATE");
        $stmtEmp->execute([$eid]);
        $empresa = $stmtEmp->fetchObject();
        if (!$empresa) {
            throw new Exception("Empresa no encontrada");
        }

        $hoy = date('Y-m-d');
        $baseFecha = ($empresa->fecha_vencimiento && $empresa->fecha_vencimiento > $hoy)
            ? $empresa->fecha_vencimiento
            : $hoy;
        $nuevaFechaVencimiento = date('Y-m-d', strtotime($baseFecha . ' + 1 month'));

        $notas = "Licencia Plan {$planElegido} vía PayPal (Orden: {$orderId}" . ($payerEmail ? " - {$payerEmail}" : "") . ")";

        $stmtPago = $bd->prepare(
            "INSERT INTO pagos_empresa (empresa_id, monto, metodo_pago, mes_pagado, fecha_vencimiento_nueva, notas)
             VALUES (?, ?, 'PAYPAL', ?, ?, ?)"
        );
        $stmtPago->execute([
            $eid,
            $monto,
            date('Y-m'),
            $nuevaFechaVencimiento,
            $notas
        ]);

        $stmtUpdate = $bd->prepare("UPDATE empresas SET plan = ?, estado = 'ACTIVO', fecha_vencimiento = ? WHERE id = ?");
        $stmtUpdate->execute([$planElegido, $nuevaFechaVencimiento, $eid]);

        $bd->commit();

        echo json_encode([
            "resultado" => true,
            "mensaje" => "¡Licencia Plan {$planElegido} activada! Válida hasta {$nuevaFechaVencimiento}",
            "fecha_vencimiento" => $nuevaFechaVencimiento,
            "plan" => $planElegido
        ]);
    } catch (Exception $e) {
        if ($bd->inTransaction()) {
            $bd->rollBack();
        }
        echo json_encode(["resultado" => false, "error" => "No se pudo procesar la renovación de licencia."]);
    }
    exit;
}

// 3. HISTORIAL DE PAGOS POR EMPRESA (SUPERADMIN)
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    requireRole(["SUPERADMIN"]);
    $empresaId = $_GET["empresa_id"] ?? null;
    if (!$empresaId) {
        echo json_encode([]);
        exit;
    }

    $stmt = $bd->prepare("SELECT * FROM pagos_empresa WHERE empresa_id = ? ORDER BY fecha_pago DESC");
    $stmt->execute([$empresaId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

// 4. REGISTRAR NUEVO PAGO MANUAL (SUPERADMIN)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    requireRole(["SUPERADMIN"]);
    if (!$datos || empty($datos->empresa_id) || empty($datos->monto)) {
        http_response_code(400);
        echo json_encode(["resultado" => false, "error" => "Datos incompletos"]);
        exit;
    }

    try {
        $bd->beginTransaction();

        $stmtEmp = $bd->prepare("SELECT fecha_vencimiento FROM empresas WHERE id = ? FOR UPDATE");
        $stmtEmp->execute([$datos->empresa_id]);
        $empresa = $stmtEmp->fetchObject();

        if (!$empresa) {
            throw new Exception("Empresa no encontrada");
        }

        $hoy = date('Y-m-d');
        $baseFecha = ($empresa->fecha_vencimiento && $empresa->fecha_vencimiento > $hoy)
            ? $empresa->fecha_vencimiento
            : $hoy;

        $nuevaFechaVencimiento = date('Y-m-d', strtotime($baseFecha . ' + 1 month'));

        $stmtPago = $bd->prepare("INSERT INTO pagos_empresa (empresa_id, monto, metodo_pago, mes_pagado, fecha_vencimiento_nueva, notas) VALUES (?,?,?,?,?,?)");
        $stmtPago->execute([
            $datos->empresa_id,
            $datos->monto,
            $datos->metodo_pago ?? 'EFECTIVO',
            $datos->mes_pagado ?? date('Y-m'),
            $nuevaFechaVencimiento,
            $datos->notas ?? ''
        ]);

        $stmtUpdate = $bd->prepare("UPDATE empresas SET estado = 'ACTIVO', fecha_vencimiento = ? WHERE id = ?");
        $stmtUpdate->execute([$nuevaFechaVencimiento, $datos->empresa_id]);

        $bd->commit();
        echo json_encode(["resultado" => true, "mensaje" => "Pago registrado. Nueva fecha: $nuevaFechaVencimiento"]);

    } catch (Exception $e) {
        if ($bd->inTransaction()) {
            $bd->rollBack();
        }
        echo json_encode(["resultado" => false, "error" => $e->getMessage()]);
    }
    exit;
}
