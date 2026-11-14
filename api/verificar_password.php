<?php
include_once "encabezado.php";
include_once "funciones.php";

$usuarioSesion = requireAuth();

$pass = json_decode(file_get_contents("php://input"));
if (!$pass || !isset($pass->password)) {
    http_response_code(400);
    echo json_encode(false);
    exit;
}

$idObjetivo = (int)$usuarioSesion->id;
$resultado = verificarPassword($pass->password, $idObjetivo);

echo json_encode($resultado);
