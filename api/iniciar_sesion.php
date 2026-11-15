<?php
include_once "encabezado.php";
include_once "funciones.php";

$usuario_p = json_decode(file_get_contents("php://input"));
if (!$usuario_p) {
	http_response_code(400);
	echo json_encode(["resultado" => false, "error" => "No se encontraron datos"]);
	exit;
}

comenzarSesionSegura();

// Protección básica contra fuerza bruta (máx. 5 intentos fallidos, bloqueo de 5 minutos)
$ahora = time();
$bloqueoHasta = $_SESSION['login_bloqueo_hasta'] ?? 0;
if ($bloqueoHasta > $ahora) {
	$segundosRestantes = $bloqueoHasta - $ahora;
	http_response_code(429);
	echo json_encode([
		"resultado" => false,
		"error" => "Demasiados intentos fallidos. Intenta de nuevo en {$segundosRestantes} segundos."
	]);
	exit;
}

$ruc = isset($usuario_p->ruc) ? trim($usuario_p->ruc) : null;
$respuesta = iniciarSesion($ruc, $usuario_p->correo ?? '', $usuario_p->password ?? '');

if ($respuesta && isset($respuesta->isSuspended) && $respuesta->isSuspended) {
	echo json_encode([
		"resultado" => false,
		"error" => "SUSPENDIDA",
		"mensaje" => "La cuenta de su restaurante está suspendida o caducada por falta de pago. ¿Deseas reactivarla pagando ahora con PayPal?",
		"empresaId" => $respuesta->empresa_id ?? $respuesta->empresa_id_detalle ?? null,
		"plan" => $respuesta->plan ?? 'BASICO'
	]);
	return;
}

if ($respuesta) {
	// Limpiar contador de intentos y regenerar ID de sesión contra fijación de sesión
	unset($_SESSION['login_intentos'], $_SESSION['login_bloqueo_hasta']);
	session_regenerate_id(true);
	$_SESSION['usuario'] = $respuesta;

	$usuario_datos = [
		"nombreUsuario" => $respuesta->nombre,
		"idUsuario" => $respuesta->id,
		"rol" => $respuesta->rol,
		"empresaId" => $respuesta->empresa_id
	];

	// Verificar si tiene la contraseña inicial (su cédula o la clave por defecto heredada)
	$usaCedula = !empty($respuesta->cedula) && verificarPassword($respuesta->cedula, $respuesta->id);
	$verificaPass = $usaCedula || verificarPassword("PacoHunterDev", $respuesta->id);
	if ($verificaPass) {
		echo json_encode(["resultado" => "cambia", "datos" => $usuario_datos]);
		return;
	}

	echo json_encode(["resultado" => true, "datos" => $usuario_datos]);
} else {
	$intentos = ($_SESSION['login_intentos'] ?? 0) + 1;
	$_SESSION['login_intentos'] = $intentos;
	if ($intentos >= 5) {
		$_SESSION['login_bloqueo_hasta'] = $ahora + 300; // 5 minutos
		$_SESSION['login_intentos'] = 0;
	}
	echo json_encode(["resultado" => false, "error" => "Correo o contraseña incorrectos"]);
}
