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

$stmtNegocio = $bd->prepare("
    SELECT i.nombre, i.telefono, i.direccion, i.correo, i.logo, i.mensaje_pie, e.razon_social
    FROM empresas e
    LEFT JOIN informacion_negocio i ON i.empresa_id = e.id
    WHERE e.id = ? AND e.estado = 'ACTIVO'
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
    SELECT i.id, i.codigo, i.nombre, i.descripcion, i.precio, i.tipo, i.categoria, i.stock,
           IFNULL(c.nombre, 'General') AS categoria_nombre
    FROM insumos i
    LEFT JOIN categorias c ON c.id = i.categoria
    WHERE i.empresa_id = ? AND (i.stock IS NULL OR i.stock > 0)
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
