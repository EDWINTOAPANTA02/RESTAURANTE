<?php
include_once "encabezado.php";
include_once "funciones.php";
include_once "middleware/Auth.php";
include_once "middleware/Tenant.php";
include_once "middleware/Rbac.php";

requireTenant();
requireRole(['ADMINISTRADOR', 'CAJERO']);

$filtros = json_decode(file_get_contents("php://input"));
$busqueda = trim($filtros->busqueda ?? '');
$empresaId = getEmpresaId();

$clientes = obtenerClientes($busqueda, $empresaId);
echo json_encode($clientes);
