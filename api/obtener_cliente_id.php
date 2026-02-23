<?php
include_once "encabezado.php";
include_once "funciones.php";
include_once "middleware/Auth.php";
include_once "middleware/Tenant.php";
include_once "middleware/Rbac.php";

requireTenant();
requireRole(['ADMINISTRADOR', 'CAJERO']);

$datos = json_decode(file_get_contents("php://input"));
$id = (int)($datos->id ?? 0);
$empresaId = getEmpresaId();

if (!$id) {
    http_response_code(422);
    echo json_encode(['error' => 'ID de cliente requerido.']);
    exit;
}

$cliente = obtenerClientePorId($id, $empresaId);
if (!$cliente) {
    http_response_code(404);
    echo json_encode(['error' => 'Cliente no encontrado.']);
    exit;
}
echo json_encode($cliente);
