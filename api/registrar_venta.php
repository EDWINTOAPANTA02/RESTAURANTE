<?php
include_once "encabezado.php";
include_once "funciones.php"; // Required for comenzarSesionSegura() used in Auth
include_once "middleware/Auth.php";
include_once "middleware/Tenant.php";
include_once "middleware/Rbac.php";

requireTenant();
requireRole(['ADMINISTRADOR', 'CAJERO', 'MESERO']);

$venta = json_decode(file_get_contents("php://input"));
if (!$venta) {
    http_response_code(500);
    exit;
}

$venta->empresa_id = getEmpresaId();

$resultado = registrarVenta($venta);
echo json_encode($resultado);
