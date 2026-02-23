<?php
$usuario_p = json_decode(file_get_contents("php://input"));
if (!$usuario_p)
	exit("No se encontraron datos");
include_once "encabezado.php";
include_once "funciones.php";
$respuesta = iniciarSesion($usuario_p->correo, $usuario_p->password);

if ($respuesta) {
	comenzarSesionSegura();
	$_SESSION['usuario'] = $respuesta;

	$usuario_datos = [
		"nombreUsuario" => $respuesta->nombre,
		"idUsuario" => $respuesta->id,
		"rol" => $respuesta->rol
	];

	// Verificar si tiene el password por defecto (ajustar según sea necesario)
	$verificaPass = verificarPassword("PacoHunterDev", $respuesta->id);
	if ($verificaPass) {
		echo json_encode(["resultado" => "cambia", "datos" => $usuario_datos]);
		return;
	}

	echo json_encode(["resultado" => true, "datos" => $usuario_datos]);
}
else {
	echo json_encode(["resultado" => false]);
}
