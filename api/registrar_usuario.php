<?php
include_once "encabezado.php";
$usuario = json_decode(file_get_contents("php://input"));
if (!$usuario) {
    http_response_code(500);
    exit;
}

include_once "funciones.php";
verificarRol(['ADMINISTRADOR']);
$cedula = isset($usuario->cedula) ? trim($usuario->cedula) : "";
if ($cedula === "" || !validarCedulaEcuatoriana($cedula)) {
    echo json_encode(["resultado" => false, "error" => "La cédula ingresada no es válida (debe tener 10 dígitos válidos)"]);
    exit;
}

$usuario->cedula = $cedula;
$usuario->password = password_hash($cedula, PASSWORD_DEFAULT);

$resultado = registrarUsuario($usuario);
echo json_encode($resultado);
