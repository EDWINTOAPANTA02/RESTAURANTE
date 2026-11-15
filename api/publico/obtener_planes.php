<?php
include_once __DIR__ . "/../encabezado.php";
include_once __DIR__ . "/../funciones.php";
include_once __DIR__ . "/../funciones_planes.php";

echo json_encode(obtenerPlanes());
