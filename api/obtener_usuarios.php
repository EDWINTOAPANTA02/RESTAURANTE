<?php
include_once "encabezado.php";
include_once "funciones.php";
verificarRol(['ADMINISTRADOR']);

$usuarios = obtenerusuarios();

echo json_encode($usuarios);