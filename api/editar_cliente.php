<?php
include_once "encabezado.php";
include_once "funciones.php";
include_once "middleware/Auth.php";
include_once "middleware/Tenant.php";
include_once "middleware/Rbac.php";

requireTenant();
requireRole(['ADMINISTRADOR', 'CAJERO']);

$datos = json_decode(file_get_contents("php://input"));
if (!$datos || empty($datos->id)) {
    http_response_code(422);
    echo json_encode(['error' => 'ID de cliente requerido.']);
    exit;
}

$empresaId = getEmpresaId();
// Cargar valores actuales para auditoría
$antes = obtenerClientePorId((int)$datos->id, $empresaId);

$resultado = editarCliente($datos, $empresaId);
if ($resultado) {
    registrarAuditoria('EDITAR_CLIENTE', 'clientes', (int)$datos->id, (array)$antes, (array)$datos);
    echo json_encode(['resultado' => true, 'mensaje' => 'Cliente actualizado.']);
}
else {
    http_response_code(422);
    echo json_encode(['resultado' => false, 'error' => 'No se pudo actualizar. Verifica cédula duplicada o correo inválido.']);
}
