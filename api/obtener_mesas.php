<?php
include_once "encabezado.php";
include_once "funciones.php";
requireTenant();

$resultado = obtenerMesas();

echo json_encode($resultado);