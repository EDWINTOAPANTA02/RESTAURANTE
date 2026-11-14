<?php
include_once "encabezado.php";
include_once "funciones.php";
requireTenant();
$filtros = json_decode(file_get_contents("php://input"));

$insumos = obtenerInsumos($filtros);

echo json_encode($insumos);