<?php
include_once "encabezado.php";
include_once "funciones.php";

verificarRol(['ADMINISTRADOR']);

$idUsuario = json_decode(file_get_contents("php://input"));
if (!$idUsuario) {
    http_response_code(500);
    exit;
}

$resultado = eliminarUsuario($idUsuario);
echo json_encode($resultado);
