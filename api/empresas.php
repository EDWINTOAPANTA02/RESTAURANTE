<?php include_once "encabezado.php";
include_once "funciones.php";
include_once "middleware/Auth.php";
include_once "middleware/Rbac.php";
requireAuth();
requireRole(["SUPERADMIN"]);
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $bd = conectarBaseDatos();
    $stmt = $bd->query("SELECT id, razon_social, ruc, correo, telefono, fecha_registro, fecha_vencimiento, estado, plan, ambiente FROM empresas ORDER BY id DESC");
    echo json_encode($stmt->fetchAll());
    exit;
}
$datos = json_decode(file_get_contents("php://input"));
if (!$datos)
    exit;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bd = conectarBaseDatos();
    try {
        $stmt = $bd->prepare("INSERT INTO empresas (razon_social, ruc, direccion_matriz, correo, telefono, estado, plan) VALUES (?,?,?,?,?,?,?)");
        $direccion = isset($datos->direccion_matriz) ? $datos->direccion_matriz : "No especificado";
        $exito = $stmt->execute([$datos->razon_social, $datos->ruc, $direccion, $datos->correo, $datos->telefono, "ACTIVO", $datos->plan]);
        if ($exito) {
            $empresaId = $bd->lastInsertId();
            if (!empty($datos->admin_correo) && !empty($datos->admin_password)) {
                $pwd = password_hash($datos->admin_password, PASSWORD_DEFAULT);
                $s2 = $bd->prepare("INSERT INTO usuarios (empresa_id, correo, nombre, telefono, password, rol) VALUES (?,?,?,?,?,?)");
                $s2->execute([$empresaId, $datos->admin_correo, $datos->admin_nombre, $datos->telefono, $pwd, "ADMINISTRADOR"]);
            }

            // 3. Inicializar tabla informacion_negocio
            $s3 = $bd->prepare("INSERT INTO informacion_negocio (empresa_id, nombre, ruc, direccion, telefono, correo) VALUES (?,?,?,?,?,?)");
            $s3->execute([$empresaId, $datos->razon_social, $datos->ruc, $direccion, $datos->telefono, $datos->correo]);

            // 4. Inicializar categorías por defecto
            $categoriasDefault = [
                ['tipo' => 'PRODUCTO', 'nombre' => 'Entradas', 'desc' => 'Platos de entrada'],
                ['tipo' => 'PRODUCTO', 'nombre' => 'Platos Fuertes', 'desc' => 'Platos principales'],
                ['tipo' => 'PRODUCTO', 'nombre' => 'Bebidas', 'desc' => 'Refrescos y jugos'],
                ['tipo' => 'INSUMO', 'nombre' => 'Materia Prima', 'desc' => 'Ingredientes básicos']
            ];
            $s4 = $bd->prepare("INSERT INTO categorias (empresa_id, tipo, nombre, descripcion) VALUES (?,?,?,?)");
            foreach ($categoriasDefault as $cat) {
                $s4->execute([$empresaId, $cat['tipo'], $cat['nombre'], $cat['desc']]);
            }

            echo json_encode(["resultado" => true, "mensaje" => "Empresa creada e inicializada exitosamente"]);
        }
        else {
            echo json_encode(["resultado" => false, "error" => "Error al guardar empresa"]);
        }
    }
    catch (\PDOException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Duplicate entry') !== false) {
            echo json_encode(["resultado" => false, "error" => "El RUC o correo ya se encuentra registrado."]);
        }
        else {
            echo json_encode(["resultado" => false, "error" => "Error de BD: " . $msg]);
        }
    }
}
if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("UPDATE empresas SET razon_social = ?, ruc = ?, direccion_matriz = ?, correo = ?, telefono = ?, estado = ?, plan = ? WHERE id = ?");
    $exito = $stmt->execute([
        $datos->razon_social,
        $datos->ruc,
        $datos->direccion_matriz,
        $datos->correo,
        $datos->telefono,
        $datos->estado,
        $datos->plan,
        $datos->id
    ]);
    echo json_encode(["resultado" => $exito]);
}
