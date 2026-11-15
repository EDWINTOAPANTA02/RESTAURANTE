<?php
include_once __DIR__ . "/../encabezado.php";
include_once __DIR__ . "/../funciones.php";

$datos = json_decode(file_get_contents("php://input"));

if (!$datos || empty($datos->razon_social) || empty($datos->admin_correo) || empty($datos->admin_password) || empty($datos->plan)) {
    http_response_code(400);
    echo json_encode(["resultado" => false, "error" => "Faltan datos obligatorios para el registro."]);
    exit;
}

$bd = conectarBaseDatos();

try {
    $bd->beginTransaction();

    // 1. Crear la empresa (Activa por 1 mes ya que viene de un pago exitoso)
    $fechaVencimiento = date('Y-m-d', strtotime('+1 month'));
    $stmtEmpresa = $bd->prepare("INSERT INTO empresas (razon_social, ruc, plan, estado, fecha_vencimiento) VALUES (?, ?, ?, 'ACTIVO', ?)");
    $stmtEmpresa->execute([
        $datos->razon_social,
        $datos->ruc ?? null,
        $datos->plan,
        $fechaVencimiento
    ]);
    $empresaId = $bd->lastInsertId();

    // 2. Crear información de negocio por defecto
    $stmtInfo = $bd->prepare("INSERT INTO informacion_negocio (empresa_id, nombre, telefono, numeroMesas, logo) VALUES (?, ?, ?, 10, '/fotos/logo.png')");
    $stmtInfo->execute([
        $empresaId,
        $datos->razon_social,
        $datos->telefono ?? '0000000000'
    ]);

    // 3. Crear el usuario ADMINISTRADOR para esta empresa
    $passHash = password_hash($datos->admin_password, PASSWORD_DEFAULT);
    $stmtUser = $bd->prepare("INSERT INTO usuarios (empresa_id, correo, nombre, password, rol, telefono) VALUES (?, ?, ?, ?, 'ADMINISTRADOR', ?)");
    $stmtUser->execute([
        $empresaId,
        $datos->admin_correo,
        $datos->admin_nombre ?? 'Administrador',
        $passHash,
        $datos->telefono ?? '0000000000'
    ]);

    // 4. Crear categorías básicas por defecto
    $stmtCat = $bd->prepare("INSERT INTO categorias (empresa_id, tipo, nombre, descripcion) VALUES (?, 'PLATILLO', 'General', 'Categoría por defecto'), (?, 'BEBIDA', 'Bebidas', 'Bebidas en general')");
    $stmtCat->execute([$empresaId, $empresaId]);

    $bd->commit();
    echo json_encode([
        "resultado" => true,
        "mensaje" => "¡Bienvenido a FoodSys! Tu restaurante ha sido registrado y activado.",
        "empresa_id" => $empresaId
    ]);

} catch (Exception $e) {
    if ($bd->inTransaction()) {
        $bd->rollBack();
    }
    http_response_code(500);
    echo json_encode(["resultado" => false, "error" => "Error al registrar la empresa: " . $e->getMessage()]);
}
