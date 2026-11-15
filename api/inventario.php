<?php
include_once "encabezado.php";
include_once "funciones.php";

$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!estaAutenticado()) {
    http_response_code(401);
    echo json_encode(["error" => "No autenticado"]);
    exit;
}

$bd = conectarBaseDatos();
$eid = getEmpresaId();
$uid = getUsuarioId();

// Auto-migración de tablas de Inventario, Recetas y Movimientos (Kardex)
$bd->exec("CREATE TABLE IF NOT EXISTS inventario_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL DEFAULT 1,
    nombre VARCHAR(150) NOT NULL,
    categoria_inv VARCHAR(40) NOT NULL DEFAULT 'PROTEINA',
    unidad VARCHAR(30) NOT NULL DEFAULT 'UND',
    stock_actual DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 5.00,
    costo_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_empresa (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$bd->exec("CREATE TABLE IF NOT EXISTS inventario_recetas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL DEFAULT 1,
    insumo_id BIGINT UNSIGNED NOT NULL,
    item_id INT NOT NULL,
    cantidad_descuento DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    UNIQUE KEY uk_insumo_item (empresa_id, insumo_id, item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$bd->exec("CREATE TABLE IF NOT EXISTS inventario_movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL DEFAULT 1,
    item_id INT NULL,
    tipo_movimiento VARCHAR(30) NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    costo_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    proveedor VARCHAR(120) NULL,
    pagado_desde_caja TINYINT(1) NOT NULL DEFAULT 0,
    usuario_id INT NOT NULL,
    fecha DATETIME NOT NULL,
    INDEX idx_emp_fecha (empresa_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

/**
 * Sincroniza el stock de los platos/bebidas del menú que estén vinculados 1-a-1 con este ítem de bodega
 */
function sincronizarStockMenu($bd, $eid, $itemId) {
    $stmtItem = $bd->prepare("SELECT stock_actual FROM inventario_items WHERE id = ? AND empresa_id = ?");
    $stmtItem->execute([$itemId, $eid]);
    $item = $stmtItem->fetchObject();
    if (!$item) return;

    $stmtRec = $bd->prepare("SELECT insumo_id, cantidad_descuento FROM inventario_recetas WHERE item_id = ? AND empresa_id = ?");
    $stmtRec->execute([$itemId, $eid]);
    $recetas = $stmtRec->fetchAll();

    foreach ($recetas as $rec) {
        $factor = floatval($rec->cantidad_descuento) > 0 ? floatval($rec->cantidad_descuento) : 1;
        $porcionesDisponibles = max(0, floor(floatval($item->stock_actual) / $factor));
        $bd->prepare("UPDATE insumos SET stock = ?, disponible = ? WHERE id = ? AND empresa_id = ?")
           ->execute([$porcionesDisponibles, ($porcionesDisponibles > 0 ? 1 : 0), $rec->insumo_id, $eid]);
    }
}

$payload = json_decode(file_get_contents("php://input"));
$accion = isset($payload->accion) ? $payload->accion : (isset($_GET['accion']) ? $_GET['accion'] : 'resumen');

if ($accion === 'resumen') {
    $inicio = (!empty($payload->inicio)) ? $payload->inicio : date('Y-m-01');
    $fin = (!empty($payload->fin)) ? $payload->fin : date('Y-m-d');

    // 1. Obtener ítems de inventario con sus platos vinculados
    $stmtItems = $bd->prepare("SELECT * FROM inventario_items WHERE empresa_id = ? AND activo = 1 ORDER BY categoria_inv ASC, nombre ASC");
    $stmtItems->execute([$eid]);
    $items = $stmtItems->fetchAll();

    // 2. Obtener recetas (vínculos entre platos del menú e ítems de bodega)
    $stmtRecetas = $bd->prepare("
        SELECT r.*, i.nombre AS nombre_plato, i.tipo AS tipo_plato, i.precio AS precio_plato, b.nombre AS nombre_item, b.unidad
        FROM inventario_recetas r
        INNER JOIN insumos i ON i.id = r.insumo_id AND i.empresa_id = r.empresa_id
        INNER JOIN inventario_items b ON b.id = r.item_id AND b.empresa_id = r.empresa_id
        WHERE r.empresa_id = ?
        ORDER BY i.nombre ASC
    ");
    $stmtRecetas->execute([$eid]);
    $recetas = $stmtRecetas->fetchAll();

    // 3. Obtener platos/bebidas del menú para poder vincularlos
    $stmtMenu = $bd->prepare("SELECT id, codigo, nombre, tipo, precio, stock FROM insumos WHERE empresa_id = ? ORDER BY tipo ASC, nombre ASC");
    $stmtMenu->execute([$eid]);
    $insumosMenu = $stmtMenu->fetchAll();

    // 4. Obtener movimientos (Kardex) en el rango de fechas
    $stmtMov = $bd->prepare("
        SELECT m.*, b.nombre AS nombre_item, b.unidad, u.nombre AS usuario_nombre
        FROM inventario_movimientos m
        LEFT JOIN inventario_items b ON b.id = m.item_id
        LEFT JOIN usuarios u ON u.id = m.usuario_id
        WHERE m.empresa_id = ? AND DATE(m.fecha) >= ? AND DATE(m.fecha) <= ?
        ORDER BY m.id DESC
        LIMIT 200
    ");
    $stmtMov->execute([$eid, $inicio, $fin]);
    $movimientos = $stmtMov->fetchAll();

    // 5. Calcular totales financieros del periodo (Ventas vs Compras/Gastos)
    $stmtVentas = $bd->prepare("SELECT COALESCE(SUM(total), 0) FROM ventas WHERE empresa_id = ? AND DATE(fecha) >= ? AND DATE(fecha) <= ?");
    $stmtVentas->execute([$eid, $inicio, $fin]);
    $totalVentasPeriodo = floatval($stmtVentas->fetchColumn());

    $stmtGastos = $bd->prepare("
        SELECT COALESCE(SUM(costo_total), 0)
        FROM inventario_movimientos
        WHERE empresa_id = ? AND tipo_movimiento IN ('COMPRA', 'GASTO_GENERAL')
          AND DATE(fecha) >= ? AND DATE(fecha) <= ?
    ");
    $stmtGastos->execute([$eid, $inicio, $fin]);
    $totalComprasPeriodo = floatval($stmtGastos->fetchColumn());

    $valorTotalBodega = 0;
    $alertasBajoStock = 0;
    foreach ($items as $it) {
        $valorTotalBodega += floatval($it->stock_actual) * floatval($it->costo_unitario);
        if (floatval($it->stock_actual) <= floatval($it->stock_minimo)) {
            $alertasBajoStock++;
        }
    }

    echo json_encode([
        "items" => $items,
        "recetas" => $recetas,
        "insumos_menu" => $insumosMenu,
        "movimientos" => $movimientos,
        "finanzas" => [
            "inicio" => $inicio,
            "fin" => $fin,
            "ventas_periodo" => round($totalVentasPeriodo, 2),
            "compras_periodo" => round($totalComprasPeriodo, 2),
            "utilidad_neta" => round($totalVentasPeriodo - $totalComprasPeriodo, 2),
            "valor_bodega" => round($valorTotalBodega, 2),
            "alertas_bajo_stock" => $alertasBajoStock
        ]
    ]);
    exit;
}

if ($accion === 'crear_item') {
    $nombre = trim($payload->nombre ?? '');
    $categoria = trim($payload->categoria_inv ?? 'PROTEINA');
    $unidad = trim($payload->unidad ?? 'UND');
    $stockInicial = floatval($payload->stock_actual ?? 0);
    $stockMinimo = floatval($payload->stock_minimo ?? 5);
    $costoTotalInicial = floatval($payload->costo_total_inicial ?? 0);
    $insumoMenuId = !empty($payload->insumo_menu_id) ? intval($payload->insumo_menu_id) : 0;

    if ($nombre === '') {
        echo json_encode(["resultado" => false, "mensaje" => "El nombre del ítem es obligatorio"]);
        exit;
    }

    $costoUnitario = ($stockInicial > 0 && $costoTotalInicial > 0)
        ? round($costoTotalInicial / $stockInicial, 4)
        : floatval($payload->costo_unitario ?? 0);

    $stmt = $bd->prepare("INSERT INTO inventario_items (empresa_id, nombre, categoria_inv, unidad, stock_actual, stock_minimo, costo_unitario) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $ok = $stmt->execute([$eid, $nombre, $categoria, $unidad, $stockInicial, $stockMinimo, $costoUnitario]);
    $itemId = $bd->lastInsertId();

    // Si ingresó stock inicial con costo, registrar movimiento en el Kardex
    if ($ok && $stockInicial > 0) {
        $stmtMov = $bd->prepare("INSERT INTO inventario_movimientos (empresa_id, item_id, tipo_movimiento, concepto, cantidad, costo_total, proveedor, pagado_desde_caja, usuario_id, fecha) VALUES (?, ?, 'COMPRA', ?, ?, ?, ?, 0, ?, ?)");
        $stmtMov->execute([$eid, $itemId, "Inventario inicial: " . $nombre, $stockInicial, $costoTotalInicial, 'Inventario Inicial', $uid, date('Y-m-d H:i:s')]);
    }

    // Si seleccionó vincular de una vez a un plato/bebida del menú
    if ($ok && $insumoMenuId > 0) {
        $bd->prepare("INSERT IGNORE INTO inventario_recetas (empresa_id, insumo_id, item_id, cantidad_descuento) VALUES (?, ?, ?, 1.00)")
           ->execute([$eid, $insumoMenuId, $itemId]);
        sincronizarStockMenu($bd, $eid, $itemId);
    }

    echo json_encode(["resultado" => $ok, "id" => $itemId]);
    exit;
}

if ($accion === 'registrar_compra') {
    $itemId = intval($payload->item_id ?? 0);
    $cantidad = floatval($payload->cantidad ?? 0);
    $costoTotal = floatval($payload->costo_total ?? 0);
    $proveedor = trim($payload->proveedor ?? '');
    $concepto = trim($payload->concepto ?? '');
    $pagadoCaja = !empty($payload->pagado_desde_caja) ? 1 : 0;

    if ($itemId <= 0 || $cantidad <= 0) {
        echo json_encode(["resultado" => false, "mensaje" => "Selecciona un ítem y una cantidad mayor a 0"]);
        exit;
    }

    $stmtItem = $bd->prepare("SELECT * FROM inventario_items WHERE id = ? AND empresa_id = ?");
    $stmtItem->execute([$itemId, $eid]);
    $item = $stmtItem->fetchObject();
    if (!$item) {
        echo json_encode(["resultado" => false, "mensaje" => "Ítem no encontrado"]);
        exit;
    }

    $nuevoStock = floatval($item->stock_actual) + $cantidad;
    $nuevoCostoUnit = ($cantidad > 0 && $costoTotal > 0)
        ? round($costoTotal / $cantidad, 4)
        : floatval($item->costo_unitario);

    $bd->prepare("UPDATE inventario_items SET stock_actual = ?, costo_unitario = ? WHERE id = ? AND empresa_id = ?")
       ->execute([$nuevoStock, $nuevoCostoUnit, $itemId, $eid]);

    $textoConcepto = $concepto !== '' ? $concepto : ("Compra de " . $cantidad . " " . $item->unidad . " de " . $item->nombre);
    $ahora = date('Y-m-d H:i:s');

    $bd->prepare("INSERT INTO inventario_movimientos (empresa_id, item_id, tipo_movimiento, concepto, cantidad, costo_total, proveedor, pagado_desde_caja, usuario_id, fecha) VALUES (?, ?, 'COMPRA', ?, ?, ?, ?, ?, ?, ?)")
       ->execute([$eid, $itemId, $textoConcepto, $cantidad, $costoTotal, $proveedor, $pagadoCaja, $uid, $ahora]);

    // Si se pagó con dinero de la Caja abierta, registrar automáticamente en gastos_caja
    if ($pagadoCaja && $costoTotal > 0) {
        $stmtCaja = $bd->prepare("SELECT id FROM turnos_caja WHERE empresa_id = ? AND estado = 'ABIERTO' ORDER BY id DESC LIMIT 1");
        $stmtCaja->execute([$eid]);
        $turno = $stmtCaja->fetchObject();
        if ($turno) {
            $bd->prepare("INSERT INTO gastos_caja (empresa_id, turno_id, concepto, monto, fecha) VALUES (?, ?, ?, ?, ?)")
               ->execute([$eid, $turno->id, "[Inventario] " . $textoConcepto, $costoTotal, $ahora]);
        }
    }

    sincronizarStockMenu($bd, $eid, $itemId);
    echo json_encode(["resultado" => true]);
    exit;
}

if ($accion === 'registrar_salida') {
    $itemId = intval($payload->item_id ?? 0);
    $cantidad = floatval($payload->cantidad ?? 0);
    $tipo = in_array($payload->tipo_movimiento ?? '', ['MERMA', 'CONSUMO_COCINA']) ? $payload->tipo_movimiento : 'CONSUMO_COCINA';
    $motivo = trim($payload->concepto ?? 'Salida manual de bodega');

    if ($itemId <= 0 || $cantidad <= 0) {
        echo json_encode(["resultado" => false, "mensaje" => "Cantidad inválida"]);
        exit;
    }

    $stmtItem = $bd->prepare("SELECT * FROM inventario_items WHERE id = ? AND empresa_id = ?");
    $stmtItem->execute([$itemId, $eid]);
    $item = $stmtItem->fetchObject();
    if (!$item) {
        echo json_encode(["resultado" => false, "mensaje" => "Ítem no encontrado"]);
        exit;
    }

    $nuevoStock = max(0, floatval($item->stock_actual) - $cantidad);
    $bd->prepare("UPDATE inventario_items SET stock_actual = ? WHERE id = ? AND empresa_id = ?")
       ->execute([$nuevoStock, $itemId, $eid]);

    $bd->prepare("INSERT INTO inventario_movimientos (empresa_id, item_id, tipo_movimiento, concepto, cantidad, costo_total, proveedor, pagado_desde_caja, usuario_id, fecha) VALUES (?, ?, ?, ?, ?, 0, '', 0, ?, ?)")
       ->execute([$eid, $itemId, $tipo, $motivo, -$cantidad, $uid, date('Y-m-d H:i:s')]);

    sincronizarStockMenu($bd, $eid, $itemId);
    echo json_encode(["resultado" => true]);
    exit;
}

if ($accion === 'registrar_gasto_general') {
    $concepto = trim($payload->concepto ?? '');
    $costoTotal = floatval($payload->costo_total ?? 0);
    $proveedor = trim($payload->proveedor ?? '');
    $pagadoCaja = !empty($payload->pagado_desde_caja) ? 1 : 0;

    if ($concepto === '' || $costoTotal <= 0) {
        echo json_encode(["resultado" => false, "mensaje" => "Ingresa el concepto y el monto del gasto"]);
        exit;
    }

    $ahora = date('Y-m-d H:i:s');
    $bd->prepare("INSERT INTO inventario_movimientos (empresa_id, item_id, tipo_movimiento, concepto, cantidad, costo_total, proveedor, pagado_desde_caja, usuario_id, fecha) VALUES (?, NULL, 'GASTO_GENERAL', ?, 1, ?, ?, ?, ?, ?)")
       ->execute([$eid, $concepto, $costoTotal, $proveedor, $pagadoCaja, $uid, $ahora]);

    if ($pagadoCaja) {
        $stmtCaja = $bd->prepare("SELECT id FROM turnos_caja WHERE empresa_id = ? AND estado = 'ABIERTO' ORDER BY id DESC LIMIT 1");
        $stmtCaja->execute([$eid]);
        $turno = $stmtCaja->fetchObject();
        if ($turno) {
            $bd->prepare("INSERT INTO gastos_caja (empresa_id, turno_id, concepto, monto, fecha) VALUES (?, ?, ?, ?, ?)")
               ->execute([$eid, $turno->id, "[Gasto] " . $concepto, $costoTotal, $ahora]);
        }
    }

    echo json_encode(["resultado" => true]);
    exit;
}

if ($accion === 'vincular_plato') {
    $insumoId = intval($payload->insumo_id ?? 0);
    $itemId = intval($payload->item_id ?? 0);
    $cantidad = floatval($payload->cantidad_descuento ?? 1);

    if ($insumoId <= 0 || $itemId <= 0 || $cantidad <= 0) {
        echo json_encode(["resultado" => false, "mensaje" => "Datos de vinculación incompletos"]);
        exit;
    }

    $stmt = $bd->prepare("INSERT INTO inventario_recetas (empresa_id, insumo_id, item_id, cantidad_descuento) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE cantidad_descuento = VALUES(cantidad_descuento)");
    $ok = $stmt->execute([$eid, $insumoId, $itemId, $cantidad]);
    sincronizarStockMenu($bd, $eid, $itemId);

    echo json_encode(["resultado" => $ok]);
    exit;
}

if ($accion === 'desvincular_plato') {
    $idReceta = intval($payload->id ?? 0);
    $stmt = $bd->prepare("DELETE FROM inventario_recetas WHERE id = ? AND empresa_id = ?");
    $ok = $stmt->execute([$idReceta, $eid]);
    echo json_encode(["resultado" => $ok]);
    exit;
}

if ($accion === 'importar_bebidas') {
    // Importa automáticamente todas las bebidas del menú a la bodega y las vincula 1 a 1
    $stmtBeb = $bd->prepare("SELECT id, nombre, stock FROM insumos WHERE empresa_id = ? AND tipo = 'BEBIDA'");
    $stmtBeb->execute([$eid]);
    $bebidas = $stmtBeb->fetchAll();
    $importadas = 0;

    foreach ($bebidas as $beb) {
        $stmtCheck = $bd->prepare("SELECT id FROM inventario_recetas WHERE empresa_id = ? AND insumo_id = ?");
        $stmtCheck->execute([$eid, $beb->id]);
        if ($stmtCheck->fetchObject()) continue;

        $stockIni = ($beb->stock !== null) ? floatval($beb->stock) : 0;
        $stmtIns = $bd->prepare("INSERT INTO inventario_items (empresa_id, nombre, categoria_inv, unidad, stock_actual, stock_minimo, costo_unitario) VALUES (?, ?, 'BEBIDA', 'UND', ?, 6, 0)");
        $stmtIns->execute([$eid, $beb->nombre, $stockIni]);
        $nuevoItemId = $bd->lastInsertId();

        $bd->prepare("INSERT INTO inventario_recetas (empresa_id, insumo_id, item_id, cantidad_descuento) VALUES (?, ?, ?, 1.00)")
           ->execute([$eid, $beb->id, $nuevoItemId]);
        $importadas++;
    }

    echo json_encode(["resultado" => true, "importadas" => $importadas]);
    exit;
}

if ($accion === 'eliminar_item') {
    $itemId = intval($payload->id ?? 0);
    $bd->prepare("DELETE FROM inventario_recetas WHERE item_id = ? AND empresa_id = ?")->execute([$itemId, $eid]);
    $ok = $bd->prepare("UPDATE inventario_items SET activo = 0 WHERE id = ? AND empresa_id = ?")->execute([$itemId, $eid]);
    echo json_encode(["resultado" => $ok]);
    exit;
}

echo json_encode(["error" => "Acción no válida"]);
