<?php
include_once "encabezado.php";
include_once "funciones.php";

$empresa = requireTenant();
$eid = $empresa->id;
$uid = getUsuarioId();
$bd = conectarBaseDatos();

$method = $_SERVER['REQUEST_METHOD'];

function calcularTotalesTurno(PDO $bd, int $eid, string $fechaDesde): array {
    $stmt = $bd->prepare("
        SELECT 
            IFNULL(SUM(CASE WHEN IFNULL(metodo_pago, 'EFECTIVO') = 'EFECTIVO' THEN total ELSE 0 END), 0) AS total_efectivo,
            IFNULL(SUM(CASE WHEN metodo_pago = 'TRANSFERENCIA' THEN total ELSE 0 END), 0) AS total_transferencia,
            IFNULL(SUM(CASE WHEN metodo_pago = 'TARJETA' THEN total ELSE 0 END), 0) AS total_tarjeta,
            IFNULL(SUM(total), 0) AS total_ventas,
            COUNT(*) AS cantidad_ventas
        FROM ventas
        WHERE empresa_id = ? AND fecha >= ?
    ");
    $stmt->execute([$eid, $fechaDesde]);
    return (array)$stmt->fetchObject();
}

if ($method === 'GET') {
    // Obtener turno abierto actual
    $stmtAbierta = $bd->prepare("
        SELECT c.*, u.nombre AS cajero
        FROM cierres_caja c
        LEFT JOIN usuarios u ON u.id = c.usuario_id
        WHERE c.empresa_id = ? AND c.estado = 'ABIERTA'
        ORDER BY c.id DESC LIMIT 1
    ");
    $stmtAbierta->execute([$eid]);
    $abierta = $stmtAbierta->fetchObject();

    if ($abierta) {
        $totales = calcularTotalesTurno($bd, $eid, $abierta->fecha_apertura);
        $abierta->total_efectivo = $totales['total_efectivo'];
        $abierta->total_transferencia = $totales['total_transferencia'];
        $abierta->total_tarjeta = $totales['total_tarjeta'];
        $abierta->total_ventas = $totales['total_ventas'];
        $abierta->cantidad_ventas = $totales['cantidad_ventas'];
    }

    // Historial de últimos 20 cierres
    $stmtHist = $bd->prepare("
        SELECT c.*, u.nombre AS cajero
        FROM cierres_caja c
        LEFT JOIN usuarios u ON u.id = c.usuario_id
        WHERE c.empresa_id = ? AND c.estado = 'CERRADA'
        ORDER BY c.id DESC LIMIT 20
    ");
    $stmtHist->execute([$eid]);
    $historial = $stmtHist->fetchAll();

    echo json_encode([
        "abierta" => $abierta ?: null,
        "historial" => $historial
    ]);
    exit;
}

if ($method === 'POST') {
    $datos = json_decode(file_get_contents("php://input"));
    $accion = $datos->accion ?? '';

    if ($accion === 'abrir') {
        $stmtCheck = $bd->prepare("SELECT id FROM cierres_caja WHERE empresa_id = ? AND estado = 'ABIERTA' LIMIT 1");
        $stmtCheck->execute([$eid]);
        if ($stmtCheck->fetch()) {
            echo json_encode(["resultado" => false, "error" => "Ya existe una caja abierta en este momento."]);
            exit;
        }

        $montoInicial = isset($datos->monto_inicial) ? (float)$datos->monto_inicial : 0.00;
        $obs = isset($datos->observaciones) ? trim($datos->observaciones) : '';

        $stmtIns = $bd->prepare("
            INSERT INTO cierres_caja (empresa_id, usuario_id, fecha_apertura, monto_inicial, observaciones, estado)
            VALUES (?, ?, NOW(), ?, ?, 'ABIERTA')
        ");
        $ok = $stmtIns->execute([$eid, $uid, $montoInicial, $obs]);
        echo json_encode(["resultado" => $ok, "id" => $bd->lastInsertId()]);
        exit;
    }

    if ($accion === 'gasto') {
        $gastoMonto = isset($datos->gasto) ? (float)$datos->gasto : 0.00;
        $motivo = isset($datos->motivo) ? trim($datos->motivo) : '';
        $stmtUpd = $bd->prepare("
            UPDATE cierres_caja 
            SET gastos = gastos + ?, 
                observaciones = TRIM(CONCAT(IFNULL(observaciones, ''), ' | Gasto $', ?, ': ', ?))
            WHERE empresa_id = ? AND estado = 'ABIERTA'
        ");
        $ok = $stmtUpd->execute([$gastoMonto, number_format($gastoMonto, 2), $motivo, $eid]);
        echo json_encode(["resultado" => $ok]);
        exit;
    }

    if ($accion === 'cerrar') {
        $stmtAbierta = $bd->prepare("SELECT * FROM cierres_caja WHERE empresa_id = ? AND estado = 'ABIERTA' ORDER BY id DESC LIMIT 1");
        $stmtAbierta->execute([$eid]);
        $abierta = $stmtAbierta->fetchObject();

        if (!$abierta) {
            echo json_encode(["resultado" => false, "error" => "No hay una caja abierta para cerrar."]);
            exit;
        }

        $totales = calcularTotalesTurno($bd, $eid, $abierta->fecha_apertura);
        $gastos = isset($datos->gastos) ? (float)$datos->gastos : (float)$abierta->gastos;
        $efectivoContado = isset($datos->efectivo_contado) ? (float)$datos->efectivo_contado : 0.00;
        $efectivoEsperado = (float)$abierta->monto_inicial + (float)$totales['total_efectivo'] - $gastos;
        $diferencia = round($efectivoContado - $efectivoEsperado, 2);
        $obs = isset($datos->observaciones) ? trim($datos->observaciones) : $abierta->observaciones;

        $stmtClose = $bd->prepare("
            UPDATE cierres_caja
            SET fecha_cierre = NOW(),
                total_efectivo = ?,
                total_transferencia = ?,
                total_tarjeta = ?,
                gastos = ?,
                efectivo_contado = ?,
                diferencia = ?,
                observaciones = ?,
                estado = 'CERRADA'
            WHERE id = ? AND empresa_id = ?
        ");
        $ok = $stmtClose->execute([
            $totales['total_efectivo'],
            $totales['total_transferencia'],
            $totales['total_tarjeta'],
            $gastos,
            $efectivoContado,
            $diferencia,
            $obs,
            $abierta->id,
            $eid
        ]);

        $stmtCerrada = $bd->prepare("SELECT c.*, u.nombre AS cajero FROM cierres_caja c LEFT JOIN usuarios u ON u.id = c.usuario_id WHERE c.id = ?");
        $stmtCerrada->execute([$abierta->id]);
        echo json_encode([
            "resultado" => $ok,
            "cierre" => $stmtCerrada->fetchObject()
        ]);
        exit;
    }

    echo json_encode(["resultado" => false, "error" => "Acción no válida"]);
}
