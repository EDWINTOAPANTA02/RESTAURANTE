<?php
/**
 * API para Gestión de Pagos Mensuales (SaaS)
 * Solo accesible por SUPERADMIN.
 */

include_once "encabezado.php";
include_once "funciones.php";
include_once "middleware/Auth.php";
include_once "middleware/Rbac.php";

// Autenticación y Rol
requireAuth();
requireRole(["SUPERADMIN"]);

$bd = conectarBaseDatos();
$datos = json_decode(file_get_contents("php://input"));

// OBTENER HISTORIAL DE PAGOS DE UNA EMPRESA
if ($_SERVER["REQUEST_METHOD"] === "GET") {
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

// REGISTRAR NUEVO PAGO
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$datos || empty($datos->empresa_id) || empty($datos->monto)) {
        http_response_code(400);
        echo json_encode(["resultado" => false, "error" => "Datos incompletos"]);
        exit;
    }

    try {
        $bd->beginTransaction();

        // 1. Obtener fecha de vencimiento actual de la empresa
        $stmtEmp = $bd->prepare("SELECT fecha_vencimiento FROM empresas WHERE id = ? FOR UPDATE");
        $stmtEmp->execute([$datos->empresa_id]);
        $empresa = $stmtEmp->fetchObject();

        if (!$empresa) {
            throw new Exception("Empresa no encontrada");
        }

        // 2. Calcular nueva fecha de vencimiento
        // Si ya está vencido, empezamos desde hoy. Si no, le sumamos al vencimiento actual.
        $hoy = date('Y-m-d');
        $baseFecha = ($empresa->fecha_vencimiento && $empresa->fecha_vencimiento > $hoy)
            ? $empresa->fecha_vencimiento
            : $hoy;

        $nuevaFechaVencimiento = date('Y-m-d', strtotime($baseFecha . ' + 1 month'));

        // 3. Insertar el pago
        $stmtPago = $bd->prepare("INSERT INTO pagos_empresa (empresa_id, monto, metodo_pago, mes_pagado, fecha_vencimiento_nueva, notas) VALUES (?,?,?,?,?,?)");
        $stmtPago->execute([
            $datos->empresa_id,
            $datos->monto,
            $datos->metodo_pago ?? 'EFECTIVO',
            $datos->mes_pagado ?? date('Y-m'),
            $nuevaFechaVencimiento,
            $datos->notas ?? ''
        ]);

        // 4. Actualizar la empresa
        $stmtUpdate = $bd->prepare("UPDATE empresas SET fecha_vencimiento = ? WHERE id = ?");
        $stmtUpdate->execute([$nuevaFechaVencimiento, $datos->empresa_id]);

        $bd->commit();
        echo json_encode(["resultado" => true, "mensaje" => "Pago registrado. Nueva fecha: $nuevaFechaVencimiento"]);

    }
    catch (Exception $e) {
        if ($bd->inTransaction())
            $bd->rollBack();
        echo json_encode(["resultado" => false, "error" => $e->getMessage()]);
    }
    exit;
}
