<?php
include_once __DIR__ . "/../encabezado.php";
include_once __DIR__ . "/../funciones.php";

$empresaId = isset($_GET['empresa']) ? (int)$_GET['empresa'] : 0;
if ($empresaId <= 0) {
    http_response_code(400);
    echo json_encode(["resultado" => false, "error" => "Empresa no especificada"]);
    exit;
}

$bd = conectarBaseDatos();

// Asegurar columna imagen y disponible en insumos
try {
    $colImg = $bd->query("SHOW COLUMNS FROM insumos LIKE 'imagen'")->fetchObject();
    if (!$colImg) {
        $bd->exec("ALTER TABLE insumos ADD COLUMN imagen VARCHAR(255) NULL DEFAULT NULL");
    }
    $colDisp = $bd->query("SHOW COLUMNS FROM insumos LIKE 'disponible'")->fetchObject();
    if (!$colDisp) {
        $bd->exec("ALTER TABLE insumos ADD COLUMN disponible TINYINT(1) NOT NULL DEFAULT 1");
    }
} catch (\Throwable $e) {
}

$stmtNegocio = $bd->prepare("
    SELECT COALESCE(NULLIF(i.nombre, ''), e.razon_social) AS nombre,
           COALESCE(NULLIF(i.telefono, ''), e.telefono) AS telefono,
           COALESCE(NULLIF(i.direccion, ''), e.direccion) AS direccion,
           COALESCE(NULLIF(i.correo, ''), e.correo) AS correo,
           i.logo, i.mensaje_pie, e.razon_social
    FROM empresas e
    LEFT JOIN informacion_negocio i ON i.empresa_id = e.id
    WHERE e.id = ? AND e.estado IN ('ACTIVO', 'ACTIVA')
    LIMIT 1
");
$stmtNegocio->execute([$empresaId]);
$negocio = $stmtNegocio->fetchObject();

if (!$negocio) {
    http_response_code(404);
    echo json_encode(["resultado" => false, "error" => "Restaurante no encontrado o inactivo"]);
    exit;
}

$stmtCats = $bd->prepare("SELECT id, tipo, nombre, descripcion FROM categorias WHERE empresa_id = ? ORDER BY nombre ASC");
$stmtCats->execute([$empresaId]);
$categorias = $stmtCats->fetchAll();

$stmtInsumos = $bd->prepare("
    SELECT i.id, i.codigo, i.nombre, i.descripcion, i.precio, i.tipo, i.categoria, i.stock, i.imagen,
           IFNULL(c.nombre, 'General') AS categoria_nombre
    FROM insumos i
    LEFT JOIN categorias c ON c.id = i.categoria
    WHERE i.empresa_id = ?
      AND (i.disponible IS NULL OR i.disponible = 1)
      AND (i.stock IS NULL OR i.stock > 0)
    ORDER BY c.nombre ASC, i.nombre ASC
");
$stmtInsumos->execute([$empresaId]);
$insumos = $stmtInsumos->fetchAll();

echo json_encode([
    "resultado" => true,
    "negocio" => $negocio,
    "categorias" => $categorias,
    "insumos" => $insumos
]);
