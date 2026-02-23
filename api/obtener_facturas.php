<?php
/**
 * Endpoint: obtener_facturas.php
 * Retorna lista de facturas de la empresa con filtros opcionales.
 * Roles: ADMINISTRADOR, CAJERO
 */
include_once "encabezado.php";
include_once "funciones.php";
include_once "middleware/Auth.php";
include_once "middleware/Tenant.php";
include_once "middleware/Rbac.php";

requireTenant();
requireRole(['ADMINISTRADOR', 'CAJERO']);

$filtros = (array)(json_decode(file_get_contents("php://input")) ?? []);
$empresaId = getEmpresaId();

$facturas = obtenerFacturas($filtros, $empresaId);
echo json_encode($facturas);
