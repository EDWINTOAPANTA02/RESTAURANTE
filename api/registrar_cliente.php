<?php
include_once "encabezado.php";
include_once "funciones.php";
include_once "middleware/Auth.php";
include_once "middleware/Tenant.php";
include_once "middleware/Rbac.php";

requireTenant();
requireRole(['ADMINISTRADOR', 'CAJERO']);

$datos = json_decode(file_get_contents("php://input"));
if (!$datos || empty($datos->nombres) || empty($datos->apellidos) || empty($datos->cedula_ruc)) {
    http_response_code(422);
    echo json_encode(['error' => 'Datos incompletos: nombres, apellidos y cedula_ruc son requeridos.']);
    exit;
}

$empresaId = getEmpresaId();
$resultado = registrarCliente($datos, $empresaId);

if ($resultado) {
    registrarAuditoria('CREAR_CLIENTE', 'clientes', 0, null, (array)$datos);
    echo json_encode(['resultado' => true, 'mensaje' => 'Cliente registrado correctamente.']);
}
else {
    http_response_code(422);
    echo json_encode(['resultado' => false, 'error' => 'No se pudo registrar. Verifica que la cédula/RUC no esté duplicada o que el correo sea válido.']);
}
