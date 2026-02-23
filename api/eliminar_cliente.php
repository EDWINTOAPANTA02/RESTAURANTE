<?php
// Cambia a INACTIVO (eliminado lógico). Solo ADMINISTRADOR puede.
// Los datos históricos de ventas/facturas vinculados se conservan.
include_once "encabezado.php";
include_once "funciones.php";
include_once "middleware/Auth.php";
include_once "middleware/Tenant.php";
include_once "middleware/Rbac.php";

requireTenant();
requireRole(['ADMINISTRADOR']);

$datos = json_decode(file_get_contents("php://input"));
$id = (int)($datos->id ?? 0);

if (!$id) {
    http_response_code(422);
    echo json_encode(['error' => 'ID de cliente requerido.']);
    exit;
}

$empresaId = getEmpresaId();
$estado = ($datos->estado ?? 'INACTIVO');
$resultado = cambiarEstadoCliente($id, $estado, $empresaId);

if ($resultado) {
    registrarAuditoria('CAMBIAR_ESTADO_CLIENTE', 'clientes', $id, null, ['estado' => $estado]);
    echo json_encode(['resultado' => true, 'mensaje' => "Cliente $estado correctamente."]);
}
else {
    http_response_code(422);
    echo json_encode(['resultado' => false, 'error' => 'No se pudo cambiar el estado.']);
}
