<?php
include_once "encabezado.php";
include_once "funciones.php";
requireTenant();
$filtros = json_decode(file_get_contents("php://input"));
$fechaInicio = (isset($filtros->inicio)) ? $filtros->inicio : date("Y-m-d");
$fechaFin = (isset($filtros->fin)) ? $filtros->fin : date("Y-m-d");
$idUsuario =  (isset($filtros->idUsuario)) ? $filtros->idUsuario : "";

$ventas = obtenerVentas($fechaInicio, $fechaFin, $idUsuario);
$ventasPorUsuario = obtenerVentasPorUsuario($fechaInicio, $fechaFin);

$usuarios = obtenerUsuarios();

$bd = conectarBaseDatos();
$eid = getEmpresaId();
$stmtFac = $bd->prepare("SELECT id, numero_serie, clave_acceso, estado_sri, mensaje_sri FROM facturas WHERE venta_id = ? AND empresa_id = ? LIMIT 1");

foreach($ventas as $venta){
    $venta->insumos = obtenerInsumosVenta($venta->id);
    $stmtFac->execute([$venta->id, $eid]);
    $venta->factura = $stmtFac->fetchObject() ?: null;
}

echo json_encode([
    "ventas" => $ventas, 
    "ventasPorUsuario" => $ventasPorUsuario,
    "usuarios" => $usuarios
]);


