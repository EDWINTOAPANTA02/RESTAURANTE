<?php
include_once "encabezado.php";
$idCategoria = json_decode(file_get_contents("php://input"));
if (!$idCategoria) {
    http_response_code(500);
    exit;
}

include_once "funciones.php";
verificarRol(['ADMINISTRADOR']);

$resultado = eliminarCategoria($idCategoria);
echo json_encode($resultado);
