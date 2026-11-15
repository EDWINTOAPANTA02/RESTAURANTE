<?php
include_once "encabezado.php";
include_once "funciones.php";
include_once "funciones_planes.php";

verificarRol(['SUPERADMIN']);

$datos = json_decode(file_get_contents("php://input"));
if (!$datos || !isset($datos->id) || !isset($datos->precio)) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

$duracion = isset($datos->duracion_meses) ? $datos->duracion_meses : 1;
$resultado = actualizarPrecioPlan($datos->id, $datos->precio, $duracion);
echo json_encode(["resultado" => $resultado]);
?>
