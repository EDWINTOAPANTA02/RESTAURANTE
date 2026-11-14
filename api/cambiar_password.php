<?php
include_once "encabezado.php";
include_once "funciones.php";

$usuarioSesion = requireAuth();

$pass = json_decode(file_get_contents("php://input"));
if (!$pass || empty($pass->password)) {
    http_response_code(400);
    echo json_encode(["error" => "No se encontraron datos válidos"]);
    exit;
}

// Solo el propio usuario (o un ADMINISTRADOR de su misma empresa) puede cambiar la contraseña
$idObjetivo = (int)$usuarioSesion->id;
if ($usuarioSesion->rol === 'ADMINISTRADOR' && !empty($pass->id)) {
    $idObjetivo = (int)$pass->id;
}

$respuesta = cambiarPassword($idObjetivo, $pass->password);
echo json_encode($respuesta);
