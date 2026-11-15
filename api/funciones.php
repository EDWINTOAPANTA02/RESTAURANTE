<?php

define('DIRECTORIO', './fotos/');
include_once "middleware/Auth.php";
include_once "middleware/Tenant.php";
include_once "sri_service.php";

function verificarTablas()
{
	$bd = conectarBaseDatos();
	$sentencia = $bd->query("SELECT COUNT(*) AS resultado FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'botanero_ventas'");
	return $sentencia->fetchAll();
}

function obtenerVentasPorMesesDeUsuario($anio, $idUsuario)
{
	$bd = conectarBaseDatos();
	$sentencia = $bd->prepare("SELECT MONTH(fecha) AS mes, SUM(total) AS totalVentas FROM ventas 
        WHERE YEAR(fecha) = ? AND idUsuario = ?
        GROUP BY MONTH(fecha) ORDER BY mes ASC");
	$sentencia->execute([$anio, $idUsuario]);
	return $sentencia->fetchAll();
}

function obtenerVentasPorDiaMes($mes, $anio, $idUsuario)
{
	$bd = conectarBaseDatos();
	$sentencia = $bd->prepare("SELECT DAY(fecha) AS dia, SUM(total) AS totalVentas
	FROM ventas
	WHERE MONTH(fecha) = ? AND YEAR(fecha) = ? AND idUsuario = ?
	GROUP BY dia
	ORDER BY dia ASC");
	$sentencia->execute([$mes, $anio, $idUsuario]);
	return $sentencia->fetchAll();
}

function obtenerVentasSemanaDeUsuario($idUsuario)
{
	$bd = conectarBaseDatos();
	$sentencia = $bd->prepare("SELECT DAYNAME(fecha) AS dia, DAYOFWEEK(fecha) AS numeroDia, 
	 SUM(total) AS totalVentas FROM ventas
     WHERE YEARWEEK(fecha)=YEARWEEK(CURDATE())
	 AND idUsuario = ?
     GROUP BY dia 
     ORDER BY fecha ASC");
	$sentencia->execute([$idUsuario]);
	return $sentencia->fetchAll();

}

function obtenerInsumosMasVendidos($limite)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT SUM(insumos_venta.precio * insumos_venta.cantidad ) 
	AS total, insumos.nombre, insumos.tipo, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria 
	FROM insumos_venta 
	INNER JOIN insumos ON insumos.id = insumos_venta.idInsumo 
	LEFT JOIN categorias ON categorias.id = insumos.categoria
	WHERE insumos.empresa_id = ?
	GROUP BY insumos_venta.idInsumo 
	ORDER BY total DESC 
	LIMIT ?");
	$sentencia->execute([$eid, (int)$limite]);
	return $sentencia->fetchAll();
}

function obtenerTotalesPorMesa()
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT SUM(total) AS total, idMesa
	FROM ventas WHERE empresa_id = ?
	GROUP BY idMesa
	ORDER BY total DESC");
	$sentencia->execute([$eid]);
	return $sentencia->fetchAll();
}

function obtenerVentasDelDia()
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT IFNULL(SUM(total),0) AS totalVentasHoy
	FROM ventas
	WHERE empresa_id = ? AND DATE(fecha) = CURDATE()");
	$sentencia->execute([$eid]);
	return $sentencia->fetchObject()->totalVentasHoy;
}

function obtenerNumeroUsuarios()
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT COUNT(*) AS numeroUsuarios
	FROM usuarios WHERE empresa_id = ?");
	$sentencia->execute([$eid]);
	return $sentencia->fetchObject()->numeroUsuarios;
}

function obtenerNumeroInsumos()
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT COUNT(*) AS numeroInsumos
	FROM insumos WHERE empresa_id = ?");
	$sentencia->execute([$eid]);
	return $sentencia->fetchObject()->numeroInsumos;
}

function obtenerTotalVentas()
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT IFNULL(SUM(total),0) AS total FROM ventas WHERE empresa_id = ?");
	$sentencia->execute([$eid]);
	return $sentencia->fetchObject()->total;
}

function obtenerNumeroMesasOcupadas()
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT COUNT(DISTINCT idMesa) AS ocupadas FROM pedidos WHERE empresa_id = ? AND estado = 'ABIERTO'");
	$sentencia->execute([$eid]);
	return $sentencia->fetchObject()->ocupadas;
}

function obtenerVentasUsuario($fechaInicio, $fechaFin)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT usuarios.nombre, SUM(ventas.total) AS totalVentas
	FROM ventas
	INNER JOIN usuarios ON usuarios.id = ventas.idUsuario
	WHERE ventas.empresa_id = ? AND (DATE(fecha) >= ? AND DATE(fecha) <= ?)
	GROUP BY ventas.idUsuario");
	$sentencia->execute([$eid, $fechaInicio, $fechaFin]);
	return $sentencia->fetchAll();
}

function obtenerVentasPorHora($fechaInicio, $fechaFin)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT DATE_FORMAT(fecha,'%H') AS hora, 
   	SUM(total) as totalVentas FROM ventas 
    WHERE empresa_id = ? AND (DATE(fecha) >= ? AND DATE(fecha) <= ?)
    GROUP BY DATE_FORMAT(fecha,'%H') 
    ORDER BY hora ASC
    ");
	$sentencia->execute([$eid, $fechaInicio, $fechaFin]);
	return $sentencia->fetchAll();
}

function obtenerVentasPorMeses($anio)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT MONTH(fecha) AS mes, SUM(total) AS totalVentas FROM ventas 
        WHERE empresa_id = ? AND YEAR(fecha) = ?
        GROUP BY MONTH(fecha) ORDER BY mes ASC");
	$sentencia->execute([$eid, $anio]);
	return $sentencia->fetchAll();
}

function obtenerVentasDiasSemana()
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT DAYNAME(fecha) AS dia, DAYOFWEEK(fecha) AS numeroDia, SUM(total) AS totalVentas FROM ventas
     WHERE empresa_id = ? AND YEARWEEK(fecha)=YEARWEEK(CURDATE())
     GROUP BY dia 
     ORDER BY fecha ASC");
	$sentencia->execute([$eid]);
	return $sentencia->fetchAll();
}

function obtenerVentasPorUsuario($fechaInicio, $fechaFin)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT IFNULL(SUM(ventas.total), 0) AS total,
	usuarios.nombre 
	FROM ventas
	INNER JOIN usuarios ON usuarios.id = ventas.idUsuario
	WHERE ventas.empresa_id = ? AND (DATE(ventas.fecha) >= ? AND DATE(ventas.fecha) <= ?)
	GROUP BY ventas.idUsuario");
	$sentencia->execute([$eid, $fechaInicio, $fechaFin]);
	return $sentencia->fetchAll();
}

function obtenerVentas($inicio, $fin, $idUsuario = "")
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$params = [$eid, $inicio, $fin];
	$sql = "SELECT ventas.id, ventas.idMesa, ventas.cliente_id,
	               COALESCE(NULLIF(ventas.cliente, ''), CONCAT(clientes.nombres, ' ', clientes.apellidos), 'CONSUMIDOR FINAL') AS cliente,
	               clientes.cedula_ruc AS cliente_cedula, clientes.correo AS cliente_correo, clientes.telefono AS cliente_telefono, clientes.direccion AS cliente_direccion,
	               ventas.fecha, ventas.total, ventas.pagado, IFNULL(ventas.metodo_pago, 'EFECTIVO') AS metodo_pago,
	               usuarios.nombre AS atendio
	        FROM ventas
	        LEFT JOIN usuarios ON usuarios.id = ventas.idUsuario
	        LEFT JOIN clientes ON clientes.id = ventas.cliente_id
	        WHERE ventas.empresa_id = ? AND DATE(ventas.fecha) >= ? AND DATE(ventas.fecha) <= ?";
	if ($idUsuario !== "") {
		$sql .= " AND ventas.idUsuario = ?";
		$params[] = $idUsuario;
	}
	$sql .= " ORDER BY ventas.id DESC";
	$sentencia = $bd->prepare($sql);
	$sentencia->execute($params);
	return $sentencia->fetchAll();
}

function obtenerVentasConUsuario($fechaInicio, $fechaFin, $idUsuario)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$valoresAEjecutar = [$eid, $fechaInicio, $fechaFin];

	$sql = "SELECT ventas.*, IFNULL(usuarios.nombre, 'NO ENCONTRADO') AS atendio 
	FROM ventas
	LEFT JOIN usuarios ON ventas.idUsuario = usuarios.id
	WHERE ventas.empresa_id = ? AND (DATE(ventas.fecha) >= ? AND DATE(ventas.fecha) <= ?)";

	if ($idUsuario !== "") {
		$sql .= " AND ventas.idUsuario = ?";
		array_push($valoresAEjecutar, $idUsuario);
	}

	$sql .= " ORDER BY ventas.id DESC";

	$sentencia = $bd->prepare($sql);
	$sentencia->execute($valoresAEjecutar);
	return $sentencia->fetchAll();
}

function obtenerInsumosVenta($idVenta)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT insumos_venta.*, insumos.nombre, insumos.codigo
	 FROM insumos_venta 
	 LEFT JOIN insumos ON insumos.id = insumos_venta.idInsumo
	 WHERE insumos_venta.idVenta = ? AND insumos_venta.empresa_id = ?");
	$sentencia->execute([$idVenta, $eid]);
	return $sentencia->fetchAll();
}

function registrarVenta($venta)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	try {
		$bd->beginTransaction();

		$cliente_id = isset($venta->cliente_id) ? $venta->cliente_id : null;
		$cliente_nombre = isset($venta->cliente) ? $venta->cliente : null;
		$tipo_factura = isset($venta->tipo_factura) ? $venta->tipo_factura : 'CONSUMIDOR_FINAL';
		$metodo_pago = isset($venta->metodo_pago) ? $venta->metodo_pago : 'EFECTIVO';
		$empresa_id = isset($venta->empresa_id) ? $venta->empresa_id : $eid; // Use $eid if not provided
		$idUsuario = isset($venta->idUsuario) ? $venta->idUsuario : getUsuarioId();
		$esCobroParcial = !empty($venta->cobro_parcial);

		// 1. Registrar la venta
		$sentencia = $bd->prepare("INSERT INTO ventas (idMesa, cliente, fecha, total, pagado, idUsuario, cliente_id, tipo_factura, metodo_pago, empresa_id) VALUES (?,?,?,?,?,?,?,?,?,?)");
		$sentencia->execute([$venta->idMesa, $cliente_nombre, date("Y-m-d H:i:s"), $venta->total, $venta->pagado, $idUsuario, $cliente_id, $tipo_factura, $metodo_pago, $empresa_id]);
		$idVenta = $bd->lastInsertId();

		// 2. Registrar los insumos de la venta y descontar stock si aplica
		registrarInsumosVenta($venta->insumos, $idVenta, $empresa_id, $bd);

		// 3. Cerrar el pedido en la base de datos (si no es un cobro parcial de división de cuenta)
		if (!$esCobroParcial) {
			$sentenciaPedido = $bd->prepare("UPDATE pedidos SET estado = 'PAGADO' WHERE idMesa = ? AND estado = 'ABIERTO' AND empresa_id = ?");
			$sentenciaPedido->execute([$venta->idMesa, $empresa_id]);
		} elseif ($esCobroParcial && !empty($venta->insumos_restantes)) {
			// Actualizar el pedido abierto dejando únicamente los insumos restantes por pagar
			$stmtPed = $bd->prepare("SELECT id FROM pedidos WHERE idMesa = ? AND estado = 'ABIERTO' AND empresa_id = ? LIMIT 1");
			$stmtPed->execute([$venta->idMesa, $empresa_id]);
			$pedActual = $stmtPed->fetchObject();
			if ($pedActual) {
				$bd->prepare("DELETE FROM detalles_pedidos WHERE idPedido = ?")->execute([$pedActual->id]);
				foreach ($venta->insumos_restantes as $insRest) {
					$caract = isset($insRest->caracteristicas) ? trim($insRest->caracteristicas) : '';
					$est = isset($insRest->estado) ? $insRest->estado : 'pendiente';
					$stmtInsD = $bd->prepare("INSERT INTO detalles_pedidos (empresa_id, idPedido, idInsumo, cantidad, precio_unitario, caracteristicas, estado) VALUES (?,?,?,?,?,?,?)");
					$stmtInsD->execute([$empresa_id, $pedActual->id, $insRest->id, $insRest->cantidad, $insRest->precio, $caract, $est]);
				}
			}
		}

		$bd->commit();

		// 4. Emitir comprobante electrónico SRI automáticamente para la venta
		try {
			emitirFacturaDesdeVenta((int)$idVenta, (int)$empresa_id, (int)$idUsuario);
		} catch (\Throwable $eFac) {
			// No interrumpir el cobro de mesa si falla la generación secundaria
		}

		return true;
	}
	catch (Exception $e) {
		$bd->rollBack();
		return ["error" => $e->getMessage()];
	}
}

function registrarInsumosVenta($insumos, $idVenta, $empresa_id = 1, $bd = null)
{
	$resultados = [];
	$eid = getEmpresaId();
	$empresa_id = ($empresa_id === 1) ? $eid : $empresa_id;

	foreach ($insumos as $insumo) {
		$sentencia = $bd->prepare("INSERT INTO insumos_venta(idInsumo, precio, cantidad, idVenta, empresa_id) VALUES(?,?,?,?,?)");
		$sentencia->execute([$insumo->id, $insumo->precio, $insumo->cantidad, $idVenta, $empresa_id]);
		// Descontar stock si el insumo maneja stock numérico
		$stmtStock = $bd->prepare("UPDATE insumos SET stock = GREATEST(0, stock - ?) WHERE id = ? AND empresa_id = ? AND stock IS NOT NULL");
		$stmtStock->execute([(int)$insumo->cantidad, $insumo->id, $empresa_id]);
		if ($sentencia)
			array_push($resultados, $sentencia);
	}
	return $resultados;
}

function obtenerMesas()
{
	$mesas = [];
	$info = obtenerInformacionLocal();
	$numeroMesas = (!empty($info) && isset($info[0]->numeroMesas)) ? (int)$info[0]->numeroMesas : 5;
	// Incluir Mesa 0 (Para Llevar / Domicilio) si tiene un pedido abierto
	$paraLlevar = leerArchivo(0);
	if ($paraLlevar['mesa']['estado'] === 'ocupada') {
		array_push($mesas, $paraLlevar);
	}
	for ($i = 1; $i <= $numeroMesas; $i++) {
		array_push($mesas, leerArchivo($i));
	}
	return $mesas;
}

function leerArchivo($numeroMesa)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT p.*, u.nombre as atiende FROM pedidos p LEFT JOIN usuarios u ON p.idUsuario = u.id WHERE p.idMesa = ? AND p.estado = 'ABIERTO' AND p.empresa_id = ?");
	$sentencia->execute([$numeroMesa, $eid]);
	$pedido = $sentencia->fetchObject();

	if ($pedido) {
		$sentenciaDetalles = $bd->prepare("SELECT d.*, i.nombre, i.codigo, i.tipo FROM detalles_pedidos d INNER JOIN insumos i ON d.idInsumo = i.id WHERE d.idPedido = ? AND i.empresa_id = ?");
		$sentenciaDetalles->execute([$pedido->id, $eid]);
		$detalles = $sentenciaDetalles->fetchAll();

		$insumos = [];
		foreach ($detalles as $detalle) {
			$insumos[] = [
				"id" => $detalle->idInsumo,
				"codigo" => $detalle->codigo,
				"nombre" => $detalle->nombre,
				"tipo" => $detalle->tipo ?? 'PLATILLO',
				"precio" => $detalle->precio_unitario,
				"cantidad" => $detalle->cantidad,
				"caracteristicas" => $detalle->caracteristicas ?? '',
				"estado" => $detalle->estado ?? 'pendiente',
				"idDetalle" => $detalle->id
			];
		}

		return [
			"mesa" => [
				"idMesa" => $pedido->idMesa,
				"idPedido" => $pedido->id,
				"fecha" => $pedido->fecha_apertura,
				"atiende" => $pedido->atiende,
				"idUsuario" => $pedido->idUsuario,
				"total" => 0, // Calculado en el frontend o sumando detalles
				"estado" => "ocupada",
				"cliente" => $pedido->cliente,
			],
			"insumos" => $insumos
		];
	}
	else {
		return [
			"mesa" => [
				"idMesa" => $numeroMesa,
				"atiende" => "",
				"idUsuario" => "",
				"total" => "",
				"estado" => "libre",
			],
			"insumos" => []
		];
	}
}

function cancelarMesa($id)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("UPDATE pedidos SET estado = 'CANCELADO' WHERE idMesa = ? AND estado = 'ABIERTO' AND empresa_id = ?");
	return $sentencia->execute([$id, $eid]);
}

function editarMesa($mesa)
{
	$bd = conectarBaseDatos();
	try {
		$bd->beginTransaction();

		// 1. Obtener ID del pedido actual
		$eid = getEmpresaId();
		$sentenciaPedido = $bd->prepare("SELECT id FROM pedidos WHERE idMesa = ? AND estado = 'ABIERTO' AND empresa_id = ?");
		$sentenciaPedido->execute([$mesa->id, $eid]);
		$pedido = $sentenciaPedido->fetchObject();

		if ($pedido) {
			// 2. Limpiar detalles anteriores (o hacer diff, pero limpiar es más simple para este MVP)
			$sentenciaLimpiar = $bd->prepare("DELETE FROM detalles_pedidos WHERE idPedido = ?");
			$sentenciaLimpiar->execute([$pedido->id]);

			// 3. Insertar nuevos detalles
			foreach ($mesa->insumos as $insumo) {
				$caracteristicas = isset($insumo->caracteristicas) ? trim($insumo->caracteristicas) : '';
				$estado = isset($insumo->estado) ? $insumo->estado : 'pendiente';
				$sentenciaDetalle = $bd->prepare("INSERT INTO detalles_pedidos (empresa_id, idPedido, idInsumo, cantidad, precio_unitario, caracteristicas, estado) VALUES (?,?,?,?,?,?,?)");
				$sentenciaDetalle->execute([$eid, $pedido->id, $insumo->id, $insumo->cantidad, $insumo->precio, $caracteristicas, $estado]);
			}
		}

		$bd->commit();
		return true;
	}
	catch (Exception $e) {
		$bd->rollBack();
		return false;
	}
}

function ocuparMesa($mesa)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("INSERT INTO pedidos (idMesa, idUsuario, cliente, fecha_apertura, estado, empresa_id) VALUES (?,?,?,?,?,?)");
	$cliente = ($mesa->cliente === "") ? "MOSTRADOR" : $mesa->cliente;
	$fecha = date("Y-m-d H:i:s");
	$sentencia->execute([$mesa->id, $mesa->idUsuario, $cliente, $fecha, 'ABIERTO', $eid]);
	$idPedido = $bd->lastInsertId();

	foreach ($mesa->insumos as $insumo) {
		$caracteristicas = isset($insumo->caracteristicas) ? trim($insumo->caracteristicas) : '';
		$estado = isset($insumo->estado) ? $insumo->estado : 'pendiente';
		$sentenciaDetalle = $bd->prepare("INSERT INTO detalles_pedidos (empresa_id, idPedido, idInsumo, cantidad, precio_unitario, caracteristicas, estado) VALUES (?,?,?,?,?,?,?)");
		$sentenciaDetalle->execute([$eid, $idPedido, $insumo->id, $insumo->cantidad, $insumo->precio, $caracteristicas, $estado]);
	}
	return true;
}

function cambiarPassword($idUsuario, $password)
{
	$bd = conectarBaseDatos();
	$passwordCod = password_hash($password, PASSWORD_DEFAULT);
	$sentencia = $bd->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
	return $sentencia->execute([$passwordCod, $idUsuario]);
}

function verificarPassword($password, $idUsuario)
{
	$bd = conectarBaseDatos();
	$sentencia = $bd->prepare("SELECT password FROM usuarios  WHERE id = ?");
	$sentencia->execute([$idUsuario]);
	$usuario = $sentencia->fetchObject();
	if ($usuario === FALSE)
		return false;
	elseif ($sentencia->rowCount() == 1) {
		$passwordVerifica = password_verify($password, $usuario->password);
		if ($usuario && $passwordVerifica) {
			return true;
		}
		else {
			return false;
		}
	}

}

function iniciarSesion($ruc, $correo, $password)
{
	$bd = conectarBaseDatos();
	$sentencia = $bd->prepare("SELECT u.*, e.estado as empresa_estado, e.plan, e.fecha_vencimiento, e.id as empresa_id_detalle FROM usuarios u 
                               INNER JOIN empresas e ON e.id = u.empresa_id
                               WHERE e.ruc = ? AND u.correo = ?");
	$sentencia->execute([$ruc, $correo]);
	$usuario = $sentencia->fetchObject();
	if ($usuario === FALSE)
		return false;
	elseif ($sentencia->rowCount() == 1) {
		$passwordVerifica = password_verify($password, $usuario->password);
		if ($usuario && $passwordVerifica) {
			$vencida = (!empty($usuario->fecha_vencimiento) && strtotime($usuario->fecha_vencimiento) < strtotime(date('Y-m-d')));
			if (($usuario->empresa_estado !== 'ACTIVO' || $vencida) && $usuario->rol !== 'SUPERADMIN') {
				$usuario->isSuspended = true;
				return $usuario;
			}
			return $usuario;
		}
		else {
			return false;
		}
	}
	return false;
}

function eliminarUsuario($idUsuario)
{
	$bd = conectarBaseDatos();
	$sentencia = $bd->prepare("DELETE FROM usuarios WHERE id = ?");
	return $sentencia->execute([$idUsuario]);
}

function editarUsuario($usuario)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$cedula = isset($usuario->cedula) ? trim($usuario->cedula) : null;
	$sentencia = $bd->prepare("UPDATE usuarios SET cedula = ?, correo = ?, nombre = ?, telefono = ?, rol = ? WHERE id = ? AND empresa_id = ?");
	return $sentencia->execute([$cedula, $usuario->correo, $usuario->nombre, $usuario->telefono, $usuario->rol, $usuario->id, $eid]);
}

function obtenerUsuarioPorId($idUsuario)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT id, cedula, correo, nombre, telefono, rol FROM usuarios WHERE id = ? AND empresa_id = ?");
	$sentencia->execute([$idUsuario, $eid]);
	return $sentencia->fetchObject();
}

function obtenerUsuarios()
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT id, cedula, correo, nombre, telefono, rol FROM usuarios WHERE empresa_id = ?");
	$sentencia->execute([$eid]);
	return $sentencia->fetchAll();
}

function getLimitesPorPlan($plan)
{
	if ($plan === 'BASICO') {
		return ['usuarios' => 5, 'insumos' => 50, 'mesas' => 10];
	}
	if ($plan === 'PRO') {
		return ['usuarios' => 15, 'insumos' => 300, 'mesas' => 30];
	}
	return ['usuarios' => 9999, 'insumos' => 9999, 'mesas' => 999]; // PREMIUM
}

function getEmpresaId(): int
{
	comenzarSesionSegura();
	$usuario = $_SESSION['usuario'] ?? null;
	if (!$usuario || empty($usuario->empresa_id)) {
		http_response_code(401);
		echo json_encode(['error' => 'Sin sesión activa (eid).']);
		exit;
	}
	return (int)$usuario->empresa_id;
}

function getUsuarioId(): int
{
	comenzarSesionSegura();
	$usuario = $_SESSION['usuario'] ?? null;
	if (!$usuario || empty($usuario->id)) {
		http_response_code(401);
		echo json_encode(['error' => 'Sin sesión activa (uid).']);
		exit;
	}
	return (int)$usuario->id;
}

function validarCedulaEcuatoriana(string $cedula): bool
{
	$limpia = trim($cedula);
	if (!preg_match('/^\d{10}$/', $limpia)) {
		return false;
	}
	$provincia = (int)substr($limpia, 0, 2);
	if (!(($provincia >= 1 && $provincia <= 24) || $provincia === 30)) {
		return false;
	}
	$tercerDigito = (int)$limpia[2];
	if ($tercerDigito < 6) {
		$coef = [2, 1, 2, 1, 2, 1, 2, 1, 2];
		$suma = 0;
		for ($i = 0; $i < 9; $i++) {
			$val = (int)$limpia[$i] * $coef[$i];
			if ($val >= 10) {
				$val -= 9;
			}
			$suma += $val;
		}
		$verificador = (10 - ($suma % 10)) % 10;
		return $verificador === (int)$limpia[9];
	}
	return true;
}

function registrarUsuario($usuario)
{
	try {
		$bd = conectarBaseDatos();
		$empresa = requireTenant();
		$eid = $empresa->id;

		// Validar límites del plan
		$limites = getLimitesPorPlan($empresa->plan);
		$stmtCount = $bd->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ?");
		$stmtCount->execute([$eid]);
		$totalActual = $stmtCount->fetchColumn();

		if ($totalActual >= $limites['usuarios']) {
			return ["resultado" => false, "error" => "Has alcanzado el límite de usuarios de tu plan (" . $limites['usuarios'] . ")"];
		}

		$rol = isset($usuario->rol) ? $usuario->rol : 'MESERO';
		$cedula = isset($usuario->cedula) ? trim($usuario->cedula) : null;
		$correo = isset($usuario->correo) ? trim($usuario->correo) : '';

		// Verificar duplicados de correo o cédula dentro de la empresa (y correo global si tiene índice único)
		$stmtDup = $bd->prepare("SELECT cedula, correo FROM usuarios WHERE correo = ? OR (empresa_id = ? AND cedula = ? AND cedula IS NOT NULL) LIMIT 1");
		$stmtDup->execute([$correo, $eid, $cedula]);
		$dup = $stmtDup->fetchObject();
		if ($dup) {
			if (!empty($cedula) && $dup->cedula === $cedula) {
				return ["resultado" => false, "error" => "Ya existe un usuario con esta cédula en tu empresa."];
			}
			return ["resultado" => false, "error" => "Este correo electrónico ya está registrado en el sistema."];
		}

		$sentencia = $bd->prepare("INSERT INTO usuarios (empresa_id, cedula, correo, nombre, telefono, password, rol) VALUES(?,?,?,?,?,?,?)");
		$exito = $sentencia->execute([$eid, $cedula, $correo, $usuario->nombre, $usuario->telefono, $usuario->password, $rol]);
		return ["resultado" => $exito];
	}
	catch (PDOException $e) {
		if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062')) {
			return ["resultado" => false, "error" => "La cédula o el correo ya están registrados en el sistema."];
		}
		return ["resultado" => false, "error" => "Error interno al registrar el usuario."];
	}
	catch (Exception $e) {
		return ["resultado" => false, "error" => "Error al procesar el registro."];
	}
}

function comenzarSesionSegura()
{
	if (session_status() === PHP_SESSION_NONE) {
		// Configuraciones para compatibilidad con CORS y localhost:8080
		$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (($_SERVER['SERVER_PORT'] ?? null) == 443);
		$cookieParams = [
			'lifetime' => 0,
			'path' => '/',
			'domain' => '', // Dominio actual
			'secure' => $esHttps,
			'httponly' => true,
			'samesite' => 'Lax'
		];

		if (PHP_VERSION_ID >= 70300) {
			session_set_cookie_params($cookieParams);
		}
		else {
			session_set_cookie_params(
				$cookieParams['lifetime'],
				$cookieParams['path'] . '; SameSite=' . $cookieParams['samesite'],
				$cookieParams['domain'],
				$cookieParams['secure'],
				$cookieParams['httponly']
			);
		}
		session_start();
	}
}

function verificarRol($rolesPermitidos)
{
	comenzarSesionSegura();
	$usuario_sesion = $_SESSION['usuario'] ?? null;
	$rol = $usuario_sesion->rol ?? 'N/A';

	if (!$usuario_sesion || !in_array($rol, $rolesPermitidos)) {
		$log_msg = "[" . date("Y-m-d H:i:s") . "] BLOQUEO RBAC: ";
		$log_msg .= "Usuario en sesión: " . ($usuario_sesion->correo ?? 'Ninguno') . " | ";
		$log_msg .= "Rol detectado: " . $rol . " | ";
		$log_msg .= "Roles permitidos: " . implode(", ", $rolesPermitidos) . "\n";
		file_put_contents("log.txt", $log_msg, FILE_APPEND);

		http_response_code(403);
		echo json_encode(["error" => "No tienes permisos para realizar esta acción"]);
		exit;
	}
}

function obtenerInsumosPorNombre($insumo)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT insumos.*, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria
	FROM insumos
	LEFT JOIN categorias ON categorias.id = insumos.categoria 
	WHERE insumos.nombre LIKE ? AND insumos.empresa_id = ?");
	$sentencia->execute(['%' . $insumo . '%', $eid]);
	return $sentencia->fetchAll();
}

function actualizarInformacionLocal($datos)
{
	try {
		$bd = conectarBaseDatos();
		$eid = getEmpresaId();
		$sentencia = $bd->prepare("UPDATE informacion_negocio SET 
            nombre = ?, 
            ruc = ?, 
            direccion = ?, 
            telefono = ?, 
            correo = ?, 
            mensaje_pie = ?, 
            impuesto = ?, 
            logo = ?, 
            numeroMesas = ? 
            WHERE empresa_id = ?");
		$exito = $sentencia->execute([
			$datos->nombre,
			$datos->ruc ?? '',
			$datos->direccion ?? '',
			$datos->telefono,
			$datos->correo ?? '',
			$datos->mensaje_pie ?? '',
			$datos->impuesto ?? 0,
			$datos->logo,
			$datos->numeroMesas,
			$eid
		]);
		return ["resultado" => $exito];
	}
	catch (Exception $e) {
		return ["resultado" => false, "error" => $e->getMessage()];
	}
}

function registrarInformacionLocal($datos)
{
	try {
		$bd = conectarBaseDatos();
		$eid = getEmpresaId();
		$sentencia = $bd->prepare("INSERT INTO informacion_negocio (empresa_id, nombre, ruc, direccion, telefono, correo, mensaje_pie, impuesto, logo, numeroMesas) VALUES(?,?,?,?,?,?,?,?,?,?)");
		$exito = $sentencia->execute([
			$eid,
			$datos->nombre,
			$datos->ruc ?? '',
			$datos->direccion ?? '',
			$datos->telefono,
			$datos->correo ?? '',
			$datos->mensaje_pie ?? '',
			$datos->impuesto ?? 0,
			$datos->logo,
			$datos->numeroMesas
		]);
		return ["resultado" => $exito];
	}
	catch (Exception $e) {
		return ["resultado" => false, "error" => $e->getMessage()];
	}
}

function obtenerInformacionLocal()
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT i.*, e.fecha_vencimiento, e.estado as empresa_estado 
                               FROM informacion_negocio i 
                               INNER JOIN empresas e ON e.id = i.empresa_id 
                               WHERE i.empresa_id = ?");
	$sentencia->execute([$eid]);
	return $sentencia->fetchAll();
}

function obtenerImagen($imagen)
{
	$imagen = str_replace('data:image/png;base64,', '', $imagen);
	$imagen = str_replace('data:image/jpeg;base64,', '', $imagen);
	$imagen = str_replace(' ', '+', $imagen);
	$data = base64_decode($imagen);
	$file = DIRECTORIO . uniqid() . '.png';


	$insertar = file_put_contents($file, $data);
	return $file;
}

function eliminarInsumo($idInsumo)
{
	$bd = conectarBaseDatos();
	$sentencia = $bd->prepare("DELETE FROM insumos WHERE id = ?");
	return $sentencia->execute([$idInsumo]);
}

function editarInsumo($insumo)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$stock = (isset($insumo->stock) && $insumo->stock !== '' && $insumo->stock !== null) ? (int)$insumo->stock : null;
	$sentencia = $bd->prepare("UPDATE insumos SET tipo = ?, codigo = ?, nombre = ?, descripcion = ?, categoria = ?, precio = ?, stock = ? WHERE id = ? AND empresa_id = ?");
	return $sentencia->execute([$insumo->tipo, $insumo->codigo, $insumo->nombre, $insumo->descripcion, $insumo->categoria, $insumo->precio, $stock, $insumo->id, $eid]);

}

function obtenerInsumoPorId($idInsumo)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT * FROM insumos WHERE id = ? AND empresa_id = ?");
	$sentencia->execute([$idInsumo, $eid]);
	return $sentencia->fetchObject();
}

function obtenerInsumos($filtros)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$valoresAEjecutar = [$eid];
	$sql = "SELECT insumos.*, IFNULL(categorias.nombre, 'NO DEFINIDA') AS categoria
	FROM insumos
	LEFT JOIN categorias ON categorias.id = insumos.categoria WHERE insumos.empresa_id = ? ";

	if ($filtros->tipo != "") {
		$sql .= " AND  insumos.tipo = ?";
		array_push($valoresAEjecutar, $filtros->tipo);
	}

	if ($filtros->categoria != "") {
		$sql .= " AND  insumos.categoria = ?";
		array_push($valoresAEjecutar, $filtros->categoria);
	}

	if ($filtros->nombre != "") {
		$sql .= " AND (insumos.nombre LIKE ? OR insumos.codigo LIKE ?)";
		array_push($valoresAEjecutar, '%' . $filtros->nombre . '%');
		array_push($valoresAEjecutar, '%' . $filtros->nombre . '%');
	}

	$sentencia = $bd->prepare($sql);
	$sentencia->execute($valoresAEjecutar);
	return $sentencia->fetchAll();
}

function registrarInsumo($insumo)
{
	try {
		$bd = conectarBaseDatos();
		$empresa = requireTenant();
		$eid = $empresa->id;

		// Validar límites del plan
		$limites = getLimitesPorPlan($empresa->plan);
		$stmtCount = $bd->prepare("SELECT COUNT(*) FROM insumos WHERE empresa_id = ?");
		$stmtCount->execute([$eid]);
		$totalActual = $stmtCount->fetchColumn();

		if ($totalActual >= $limites['insumos']) {
			return ["resultado" => false, "error" => "Has alcanzado el límite de insumos de tu plan (" . $limites['insumos'] . ")"];
		}

		$stock = (isset($insumo->stock) && $insumo->stock !== '' && $insumo->stock !== null) ? (int)$insumo->stock : null;
		$sentencia = $bd->prepare("INSERT INTO insumos (empresa_id, codigo, nombre, descripcion, precio, tipo, categoria, stock) VALUES (?,?,?,?,?,?,?,?)");
		$exito = $sentencia->execute([$eid, $insumo->codigo, $insumo->nombre, $insumo->descripcion, $insumo->precio, $insumo->tipo, $insumo->categoria, $stock]);
		return ["resultado" => $exito];
	}
	catch (Exception $e) {
		return ["resultado" => false, "error" => "Error al registrar insumo: " . $e->getMessage()];
	}
}

function obtenerCategoriasPorTipo($tipo)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT * FROM categorias WHERE empresa_id = ? AND tipo = ?");
	$sentencia->execute([$eid, $tipo]);
	return $sentencia->fetchAll();
}


function eliminarCategoria($idCategoria)
{
	$bd = conectarBaseDatos();
	$sentencia = $bd->prepare("DELETE FROM categorias WHERE id = ?");
	return $sentencia->execute([$idCategoria]);
}

function editarCategoria($categoria)
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("UPDATE categorias SET tipo = ?, nombre = ?, descripcion = ? WHERE id = ? AND empresa_id = ?");
	return $sentencia->execute([$categoria->tipo, $categoria->nombre, $categoria->descripcion, $categoria->id, $eid]);
}

function registrarCategoria($categoria)
{
	try {
		$bd = conectarBaseDatos();
		$eid = getEmpresaId();
		$sentencia = $bd->prepare("INSERT INTO categorias (empresa_id, tipo, nombre, descripcion) VALUES (?,?,?,?)");
		return $sentencia->execute([$eid, $categoria->tipo, $categoria->nombre, $categoria->descripcion]);
	}
	catch (Exception $e) {
		return false;
	}
}

function obtenerCategorias()
{
	$bd = conectarBaseDatos();
	$eid = getEmpresaId();
	$sentencia = $bd->prepare("SELECT id, tipo, nombre, descripcion FROM categorias WHERE empresa_id = ?");
	$sentencia->execute([$eid]);
	return $sentencia->fetchAll();
}

function conectarBaseDatos()
{
	$configPath = __DIR__ . '/config.php';
	$config = file_exists($configPath) ? require $configPath : require __DIR__ . '/config.example.php';
	$dbConfig = $config['db'];

	$host = $dbConfig['host'];
	$port = $dbConfig['port'];
	$db = $dbConfig['name'];
	$user = $dbConfig['user'];
	$pass = $dbConfig['pass'];
	$charset = 'utf8mb4';

	$options = [
		\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
		\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
		\PDO::ATTR_EMULATE_PREPARES => false,
	];
	$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
	try {
		$pdo = new \PDO($dsn, $user, $pass, $options);
		return $pdo;
	}
	catch (\PDOException $e) {
		throw new \PDOException($e->getMessage(), (int)$e->getCode());
	}
}

// ══════════════════════════════════════════════════════════════
//  AUDITORÍA
// ══════════════════════════════════════════════════════════════

function registrarAuditoria(string $accion, string $tabla, int $registroId,
	?array $antes = null, ?array $despues = null): void
{
	try {
		$sesion = $_SESSION['usuario'] ?? null;
		$bd = conectarBaseDatos();
		$stmt = $bd->prepare(
			"INSERT INTO audit_logs
                (empresa_id, usuario_id, accion, tabla, registro_id, ip, datos_antes, datos_despues)
             VALUES (?,?,?,?,?,?,?,?)"
		);
		$stmt->execute([
			$sesion->empresa_id ?? null,
			$sesion->id ?? null,
			$accion, $tabla, $registroId,
			$_SERVER['REMOTE_ADDR'] ?? null,
			$antes ? json_encode($antes) : null,
			$despues ? json_encode($despues) : null,
		]);
	}
	catch (\Exception $e) {
		// Auditoría nunca debe interrumpir el flujo principal
		error_log('[AUDIT ERROR] ' . $e->getMessage());
	}
}

// ══════════════════════════════════════════════════════════════
//  CLIENTES (multitenant)
// ══════════════════════════════════════════════════════════════

function registrarCliente(object $cliente, int $empresaId): bool
{
	// Validaciones backend
	$cedula = trim($cliente->cedula_ruc ?? '');
	if (!$cedula || strlen($cedula) < 5 || strlen($cedula) > 20) {
		return false;
	}
	$correo = trim($cliente->correo ?? '');
	if ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
		return false;
	}

	$bd = conectarBaseDatos();
	try {
		$stmt = $bd->prepare(
			"INSERT INTO clientes
	            (empresa_id, nombres, apellidos, tipo_id, cedula_ruc, telefono, direccion, correo)
	         VALUES (?,?,?,?,?,?,?,?)"
		);
		return $stmt->execute([
			$empresaId,
			trim($cliente->nombres),
			trim($cliente->apellidos),
			$cliente->tipo_id ?? 'CEDULA',
			$cedula,
			trim($cliente->telefono ?? ''),
			trim($cliente->direccion ?? ''),
			$correo ?: null,
		]);
	}
	catch (\PDOException $e) {
		return false;
	}
}

function obtenerClientes(string $busqueda, int $empresaId): array
{
	$bd = conectarBaseDatos();
	$busqueda = '%' . trim($busqueda) . '%';
	$stmt = $bd->prepare(
		"SELECT id, nombres, apellidos, tipo_id, cedula_ruc,
                telefono, direccion, correo, estado, fecha_registro
         FROM clientes
         WHERE empresa_id = ?
           AND (nombres LIKE ? OR apellidos LIKE ? OR cedula_ruc LIKE ?)
         ORDER BY nombres ASC"
	);
	$stmt->execute([$empresaId, $busqueda, $busqueda, $busqueda]);
	return $stmt->fetchAll();
}

function obtenerClientePorId(int $id, int $empresaId): ?object
{
	$bd = conectarBaseDatos();
	$stmt = $bd->prepare(
		"SELECT * FROM clientes WHERE id = ? AND empresa_id = ? LIMIT 1"
	);
	$stmt->execute([$id, $empresaId]);
	$cliente = $stmt->fetchObject();
	return $cliente ?: null;
}

function editarCliente(object $cliente, int $empresaId): bool
{
	$correo = trim($cliente->correo ?? '');
	if ($correo && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
		return false;
	}

	$bd = conectarBaseDatos();
	$stmt = $bd->prepare(
		"UPDATE clientes
         SET nombres = ?, apellidos = ?, tipo_id = ?, cedula_ruc = ?,
             telefono = ?, direccion = ?, correo = ?
         WHERE id = ? AND empresa_id = ?" // empresa_id en WHERE = aislamiento garantizado
	);
	return $stmt->execute([
		trim($cliente->nombres),
		trim($cliente->apellidos),
		$cliente->tipo_id ?? 'CEDULA',
		trim($cliente->cedula_ruc),
		trim($cliente->telefono ?? ''),
		trim($cliente->direccion ?? ''),
		$correo ?: null,
		(int)$cliente->id,
		$empresaId,
	]);
}

function cambiarEstadoCliente(int $id, string $estado, int $empresaId): bool
{
	if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
		return false;
	}
	$bd = conectarBaseDatos();
	$stmt = $bd->prepare(
		"UPDATE clientes SET estado = ? WHERE id = ? AND empresa_id = ?"
	);
	return $stmt->execute([$estado, $id, $empresaId]);
}

// ══════════════════════════════════════════════════════════════
//  SECUENCIALES (con bloqueo para concurrencia)
// ══════════════════════════════════════════════════════════════

/**
 * Obtiene y reserva el siguiente número de secuencial para un punto de emisión.
 * Usa SELECT ... FOR UPDATE para evitar duplicados con concurrencia.
 * Retorna string formateado a 9 dígitos, p.ej. "000000001".
 */
function obtenerSiguienteSecuencial(int $empresaId, int $puntoEmisionId, string $tipo): string
{
	$tiposValidos = ['FACTURA', 'NOTA_CREDITO', 'NOTA_DEBITO', 'RETENCION'];
	if (!in_array($tipo, $tiposValidos, true)) {
		throw new \InvalidArgumentException("Tipo de comprobante inválido: $tipo");
	}

	$bd = conectarBaseDatos();
	$bd->beginTransaction();
	try {
		// Bloqueo exclusivo de fila
		$stmt = $bd->prepare(
			"SELECT ultimo_numero FROM secuenciales
             WHERE empresa_id = ? AND punto_emision_id = ? AND tipo_comprobante = ?
             FOR UPDATE"
		);
		$stmt->execute([$empresaId, $puntoEmisionId, $tipo]);
		$fila = $stmt->fetchObject();

		if (!$fila) {
			// Primera vez: insertar con 0 y luego incrementar
			$ins = $bd->prepare(
				"INSERT INTO secuenciales (empresa_id, punto_emision_id, tipo_comprobante, ultimo_numero)
                 VALUES (?,?,?,0)"
			);
			$ins->execute([$empresaId, $puntoEmisionId, $tipo]);
			$siguiente = 1;
		}
		else {
			$siguiente = $fila->ultimo_numero + 1;
		}

		$upd = $bd->prepare(
			"UPDATE secuenciales SET ultimo_numero = ?
             WHERE empresa_id = ? AND punto_emision_id = ? AND tipo_comprobante = ?"
		);
		$upd->execute([$siguiente, $empresaId, $puntoEmisionId, $tipo]);
		$bd->commit();
		return str_pad($siguiente, 9, '0', STR_PAD_LEFT);
	}
	catch (\Exception $e) {
		$bd->rollBack();
		throw $e;
	}
}

// ══════════════════════════════════════════════════════════════
//  EMPRESAS
// ══════════════════════════════════════════════════════════════

function obtenerEmpresaPorId(int $empresaId): ?object
{
	$bd = conectarBaseDatos();
	$stmt = $bd->prepare("SELECT * FROM empresas WHERE id = ? LIMIT 1");
	$stmt->execute([$empresaId]);
	$emp = $stmt->fetchObject();
	return $emp ?: null;
}

// ══════════════════════════════════════════════════════════════
//  SUCURSALES Y PUNTOS DE EMISIÓN
// ══════════════════════════════════════════════════════════════

function obtenerSucursales(int $empresaId): array
{
	$bd = conectarBaseDatos();
	$stmt = $bd->prepare(
		"SELECT s.*, pe.codigo AS codigo_punto, pe.id AS punto_emision_id
         FROM sucursales s
         LEFT JOIN puntos_emision pe ON pe.sucursal_id = s.id AND pe.empresa_id = s.empresa_id
         WHERE s.empresa_id = ? AND s.estado = 'ACTIVA'
         ORDER BY s.codigo"
	);
	$stmt->execute([$empresaId]);
	return $stmt->fetchAll();
}

// ══════════════════════════════════════════════════════════════
//  FACTURACIÓN (separada de ventas)
// ══════════════════════════════════════════════════════════════

/**
 * Genera los 49 dígitos de la clave de acceso SRI.
 * Módulo 11 con pesos 2-7 ciclicamente.
 */
function generarClaveAcceso(
	string $fechaEmision, // ddmmaaaa
	string $tipoComp, // 01=factura
	string $ruc, // 13 dígitos
	int $ambiente, // 1 o 2
	string $serie, // SSS+PPP (6 dígitos)
	string $secuencial, // 9 dígitos
	string $codigoNumerico, // 8 dígitos aleatorios
	string $tipoEmision = '1'): string
{
	$base = $fechaEmision . $tipoComp . $ruc . $ambiente . $serie . $secuencial . $codigoNumerico . $tipoEmision;
	// Calcular dígito verificador (módulo 11)
	$suma = 0;
	$peso = 2;
	for ($i = strlen($base) - 1; $i >= 0; $i--) {
		$suma += (int)$base[$i] * $peso;
		$peso = ($peso === 7) ? 2 : $peso + 1;
	}
	$residuo = $suma % 11;
	$dv = ($residuo === 0) ? 0 : (($residuo === 1) ? 1 : 11 - $residuo);
	return $base . $dv;
}

function obtenerFacturas(array $filtros, int $empresaId): array
{
	$bd = conectarBaseDatos();
	$params = [$empresaId];
	$where = "f.empresa_id = ?";

	if (!empty($filtros['estado_sri'])) {
		$where .= " AND f.estado_sri = ?";
		$params[] = $filtros['estado_sri'];
	}
	if (!empty($filtros['desde'])) {
		$where .= " AND DATE(f.fecha_emision) >= ?";
		$params[] = $filtros['desde'];
	}
	if (!empty($filtros['hasta'])) {
		$where .= " AND DATE(f.fecha_emision) <= ?";
		$params[] = $filtros['hasta'];
	}

	$stmt = $bd->prepare(
		"SELECT f.id, f.numero_serie, f.clave_acceso, f.fecha_emision,
                f.total, f.estado_sri, f.ambiente_sri,
                CONCAT(c.nombres, ' ', c.apellidos) AS cliente_nombre,
                c.cedula_ruc
         FROM facturas f
         LEFT JOIN clientes c ON c.id = f.cliente_id AND c.empresa_id = f.empresa_id
         WHERE $where
         ORDER BY f.fecha_emision DESC"
	);
	$stmt->execute($params);
	return $stmt->fetchAll();
}

function obtenerFacturaPorId(int $id, int $empresaId): ?object
{
	$bd = conectarBaseDatos();
	$stmt = $bd->prepare(
		"SELECT f.*,
                CONCAT(c.nombres, ' ', c.apellidos) AS cliente_nombre,
                c.cedula_ruc, c.correo AS cliente_correo, c.direccion AS cliente_dir
         FROM facturas f
         LEFT JOIN clientes c ON c.id = f.cliente_id AND c.empresa_id = f.empresa_id
         WHERE f.id = ? AND f.empresa_id = ?
         LIMIT 1"
	);
	$stmt->execute([$id, $empresaId]);
	$fac = $stmt->fetchObject();
	return $fac ?: null;
}

function obtenerDetallesFactura(int $facturaId, int $empresaId): array
{
	$bd = conectarBaseDatos();
	$stmt = $bd->prepare(
		"SELECT * FROM detalles_factura
         WHERE factura_id = ? AND empresa_id = ?
         ORDER BY id"
	);
	$stmt->execute([$facturaId, $empresaId]);
	return $stmt->fetchAll();
}

function asegurarSucursalYPuntoEmision(int $empresaId): object
{
	$sucursales = obtenerSucursales($empresaId);
	foreach ($sucursales as $s) {
		if (!empty($s->punto_emision_id)) {
			return $s;
		}
	}

	$bd = conectarBaseDatos();
	$empresa = obtenerEmpresaPorId($empresaId);
	$direccion = $empresa->direccion_matriz ?? 'Matriz Principal';

	$stmtSuc = $bd->prepare(
		"INSERT INTO sucursales (empresa_id, codigo, nombre, direccion, estado)
         VALUES (?, '001', 'Matriz', ?, 'ACTIVA')"
	);
	$stmtSuc->execute([$empresaId, $direccion]);
	$sucursalId = (int)$bd->lastInsertId();

	$stmtPe = $bd->prepare(
		"INSERT INTO puntos_emision (empresa_id, sucursal_id, codigo, nombre, estado)
         VALUES (?, ?, '001', 'Caja Principal', 'ACTIVO')"
	);
	$stmtPe->execute([$empresaId, $sucursalId]);
	$peId = (int)$bd->lastInsertId();

	return (object)[
		'id' => $sucursalId,
		'codigo' => '001',
		'direccion' => $direccion,
		'punto_emision_id' => $peId,
		'codigo_punto' => '001'
	];
}

function emitirFacturaDesdeVenta(int $ventaId, int $empresaId, int $usuarioId): array
{
	$bd = conectarBaseDatos();

	// Evitar duplicar factura si ya fue emitida para esta venta
	$stmtExist = $bd->prepare("SELECT id, numero_serie, clave_acceso, estado_sri, total FROM facturas WHERE venta_id = ? AND empresa_id = ? LIMIT 1");
	$stmtExist->execute([$ventaId, $empresaId]);
	$existente = $stmtExist->fetchObject();
	if ($existente) {
		return ["resultado" => true, "factura" => $existente];
	}

	$stmtVenta = $bd->prepare("SELECT * FROM ventas WHERE id = ? AND empresa_id = ? LIMIT 1");
	$stmtVenta->execute([$ventaId, $empresaId]);
	$venta = $stmtVenta->fetchObject();
	if (!$venta) {
		return ["resultado" => false, "error" => "Venta no encontrada para esta empresa."];
	}

	$empresa = obtenerEmpresaPorId($empresaId);
	if (!$empresa) {
		return ["resultado" => false, "error" => "Empresa no encontrada."];
	}

	$cliente = null;
	if (!empty($venta->cliente_id)) {
		$cliente = obtenerClientePorId((int)$venta->cliente_id, $empresaId);
	}

	$suc = asegurarSucursalYPuntoEmision($empresaId);
	$secuencial = obtenerSiguienteSecuencial($empresaId, (int)$suc->punto_emision_id, 'FACTURA');
	$numeroSerie = "{$suc->codigo}-{$suc->codigo_punto}-{$secuencial}";

	$rucEmisor = str_pad(preg_replace('/\D/', '', $empresa->ruc), 13, '0', STR_PAD_RIGHT);
	$ambiente = (int)($empresa->ambiente ?? 1);
	$tipoEmision = (string)($empresa->tipo_emision ?? 1);
	$codigoNumerico = str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);

	$claveAcceso = generarClaveAcceso(
		date('dmY', strtotime($venta->fecha)),
		'01',
		$rucEmisor,
		$ambiente,
		$suc->codigo . $suc->codigo_punto,
		$secuencial,
		$codigoNumerico,
		$tipoEmision
	);

	$total = round((float)$venta->total, 2);
	$tarifaIva = 15;
	$subtotalIva = round($total / (1 + ($tarifaIva / 100)), 2);
	$ivaValor = round($total - $subtotalIva, 2);

	$stmtItems = $bd->prepare(
		"SELECT iv.precio, iv.cantidad, i.nombre, i.codigo
         FROM insumos_venta iv
         INNER JOIN insumos i ON i.id = iv.idInsumo
         WHERE iv.idVenta = ? AND iv.empresa_id = ?"
	);
	$stmtItems->execute([$ventaId, $empresaId]);
	$items = $stmtItems->fetchAll();

	$identificacionComprador = $cliente ? $cliente->cedula_ruc : '9999999999999';
	$razonSocialComprador = $cliente ? trim($cliente->nombres . ' ' . $cliente->apellidos) : 'CONSUMIDOR FINAL';
	$tipoIdComprador = ($identificacionComprador === '9999999999999')
		? '07'
		: (strlen($identificacionComprador) === 13 ? '04' : '05');

	$detallesXml = "  <detalles>\n";
	foreach ($items as $item) {
		$cant = number_format((float)$item->cantidad, 2, '.', '');
		$lineaTotal = round((float)$item->precio * (float)$item->cantidad, 2);
		$lineaSub = round($lineaTotal / (1 + ($tarifaIva / 100)), 2);
		$lineaIva = round($lineaTotal - $lineaSub, 2);
		$pUnit = ((float)$item->cantidad > 0) ? number_format($lineaSub / (float)$item->cantidad, 2, '.', '') : '0.00';
		$subStr = number_format($lineaSub, 2, '.', '');
		$ivaStr = number_format($lineaIva, 2, '.', '');
		$codItem = htmlspecialchars($item->codigo ?: 'PROD01', ENT_XML1);
		$descItem = htmlspecialchars($item->nombre ?: 'CONSUMO', ENT_XML1);

		$detallesXml .= "    <detalle>\n"
			. "      <codigoPrincipal>{$codItem}</codigoPrincipal>\n"
			. "      <descripcion>{$descItem}</descripcion>\n"
			. "      <cantidad>{$cant}</cantidad>\n"
			. "      <precioUnitario>{$pUnit}</precioUnitario>\n"
			. "      <descuento>0.00</descuento>\n"
			. "      <precioTotalSinImpuesto>{$subStr}</precioTotalSinImpuesto>\n"
			. "      <impuestos>\n"
			. "        <impuesto>\n"
			. "          <codigo>2</codigo>\n"
			. "          <codigoPorcentaje>4</codigoPorcentaje>\n"
			. "          <tarifa>15.00</tarifa>\n"
			. "          <baseImponible>{$subStr}</baseImponible>\n"
			. "          <valor>{$ivaStr}</valor>\n"
			. "        </impuesto>\n"
			. "      </impuestos>\n"
			. "    </detalle>\n";
	}
	$detallesXml .= "  </detalles>\n";

	$subTotStr = number_format($subtotalIva, 2, '.', '');
	$ivaTotStr = number_format($ivaValor, 2, '.', '');
	$totStr = number_format($total, 2, '.', '');

	$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
		. "<factura id=\"comprobante\" version=\"1.1.0\">\n"
		. "  <infoTributaria>\n"
		. "    <ambiente>{$ambiente}</ambiente>\n"
		. "    <tipoEmision>{$tipoEmision}</tipoEmision>\n"
		. "    <razonSocial>" . htmlspecialchars($empresa->razon_social, ENT_XML1) . "</razonSocial>\n"
		. "    <nombreComercial>" . htmlspecialchars($empresa->razon_social, ENT_XML1) . "</nombreComercial>\n"
		. "    <ruc>{$rucEmisor}</ruc>\n"
		. "    <claveAcceso>{$claveAcceso}</claveAcceso>\n"
		. "    <codDoc>01</codDoc>\n"
		. "    <estab>{$suc->codigo}</estab>\n"
		. "    <ptoEmi>{$suc->codigo_punto}</ptoEmi>\n"
		. "    <secuencial>{$secuencial}</secuencial>\n"
		. "    <dirMatriz>" . htmlspecialchars($empresa->direccion_matriz, ENT_XML1) . "</dirMatriz>\n"
		. "  </infoTributaria>\n"
		. "  <infoFactura>\n"
		. "    <fechaEmision>" . date('d/m/Y', strtotime($venta->fecha)) . "</fechaEmision>\n"
		. "    <dirEstablecimiento>" . htmlspecialchars($empresa->direccion_matriz, ENT_XML1) . "</dirEstablecimiento>\n"
		. "    <obligadoContabilidad>NO</obligadoContabilidad>\n"
		. "    <tipoIdentificacionComprador>{$tipoIdComprador}</tipoIdentificacionComprador>\n"
		. "    <razonSocialComprador>" . htmlspecialchars($razonSocialComprador, ENT_XML1) . "</razonSocialComprador>\n"
		. "    <identificacionComprador>{$identificacionComprador}</identificacionComprador>\n"
		. "    <totalSinImpuestos>{$subTotStr}</totalSinImpuestos>\n"
		. "    <totalDescuento>0.00</totalDescuento>\n"
		. "    <totalConImpuestos>\n"
		. "      <totalImpuesto>\n"
		. "        <codigo>2</codigo>\n"
		. "        <codigoPorcentaje>4</codigoPorcentaje>\n"
		. "        <baseImponible>{$subTotStr}</baseImponible>\n"
		. "        <valor>{$ivaTotStr}</valor>\n"
		. "      </totalImpuesto>\n"
		. "    </totalConImpuestos>\n"
		. "    <propina>0.00</propina>\n"
		. "    <importeTotal>{$totStr}</importeTotal>\n"
		. "    <moneda>DOLAR</moneda>\n"
		. "    <pagos>\n"
		. "      <pago>\n"
		. "        <formaPago>01</formaPago>\n"
		. "        <total>{$totStr}</total>\n"
		. "      </pago>\n"
		. "    </pagos>\n"
		. "  </infoFactura>\n"
		. $detallesXml
		. "</factura>";

	$xml = firmarXmlSri($xml, $empresaId);

	$bd->beginTransaction();
	try {
		$stmtIns = $bd->prepare(
			"INSERT INTO facturas
             (empresa_id, sucursal_id, punto_emision_id, venta_id, cliente_id,
              numero_serie, clave_acceso, ambiente_sri, fecha_emision,
              subtotal_0, subtotal_iva, descuento, iva_valor, total,
              estado_sri, xml_autorizado, usuario_id)
             VALUES (?,?,?,?,?,?,?,?,?,0.00,?,0.00,?,?,'PENDIENTE',?,?)"
		);
		$stmtIns->execute([
			$empresaId,
			(int)$suc->id,
			(int)$suc->punto_emision_id,
			$ventaId,
			$cliente ? (int)$cliente->id : null,
			$numeroSerie,
			$claveAcceso,
			$ambiente,
			$venta->fecha,
			$subtotalIva,
			$ivaValor,
			$total,
			$xml,
			$usuarioId
		]);
		$facturaId = (int)$bd->lastInsertId();

		$stmtDet = $bd->prepare(
			"INSERT INTO detalles_factura
             (factura_id, empresa_id, descripcion, codigo_principal, cantidad,
              precio_unitario, descuento, subtotal, tarifa_iva, iva_valor)
             VALUES (?,?,?,?,?,?,0.00,?,?,?)"
		);
		foreach ($items as $item) {
			$lineaTotal = round((float)$item->precio * (float)$item->cantidad, 2);
			$lineaSub = round($lineaTotal / (1 + ($tarifaIva / 100)), 2);
			$lineaIva = round($lineaTotal - $lineaSub, 2);
			$precioUnitSinIva = ((float)$item->cantidad > 0) ? round($lineaSub / (float)$item->cantidad, 4) : 0;

			$stmtDet->execute([
				$facturaId,
				$empresaId,
				$item->nombre,
				$item->codigo,
				$item->cantidad,
				$precioUnitSinIva,
				$lineaSub,
				$tarifaIva,
				$lineaIva
			]);
		}

		$bd->commit();

		registrarAuditoria(
			'EMITIR_FACTURA',
			'facturas',
			$facturaId,
			null,
			['numero_serie' => $numeroSerie, 'clave_acceso' => $claveAcceso, 'total' => $total]
		);

		return [
			"resultado" => true,
			"factura" => (object)[
				"id" => $facturaId,
				"numero_serie" => $numeroSerie,
				"clave_acceso" => $claveAcceso,
				"estado_sri" => "PENDIENTE",
				"total" => $total
			]
		];
	} catch (\Throwable $e) {
		$bd->rollBack();
		return ["resultado" => false, "error" => "No se pudo emitir la factura."];
	}
}