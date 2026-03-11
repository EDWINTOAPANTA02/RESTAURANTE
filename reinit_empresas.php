<?php
$host = 'localhost';
$usuario = 'root';
$password = '';
$port = 3307;
$dbname = 'botanero_ventas';

try {
    $conexion = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all companies
    $empresas = $conexion->query("SELECT id, razon_social, ruc, correo, telefono, direccion_matriz FROM empresas")->fetchAll(PDO::FETCH_OBJ);

    foreach ($empresas as $e) {
        echo "Inicializando Empresa ID: {$e->id} ({$e->razon_social})...\n";

        // 1. Informacion Negocio
        $checkInfo = $conexion->prepare("SELECT id FROM informacion_negocio WHERE empresa_id = ?");
        $checkInfo->execute([$e->id]);
        if ($checkInfo->rowCount() == 0) {
            $s3 = $conexion->prepare("INSERT INTO informacion_negocio (empresa_id, nombre, ruc, direccion, telefono, correo) VALUES (?,?,?,?,?,?)");
            $s3->execute([$e->id, $e->razon_social, $e->ruc, $e->direccion_matriz, $e->telefono, $e->correo]);
            echo "- Informacion Negocio Creada.\n";
        }

        // 2. Categorias Default
        $checkCat = $conexion->prepare("SELECT id FROM categorias WHERE empresa_id = ?");
        $checkCat->execute([$e->id]);
        if ($checkCat->rowCount() == 0) {
            $categoriasDefault = [
                ['tipo' => 'PRODUCTO', 'nombre' => 'Entradas', 'desc' => 'Platos de entrada'],
                ['tipo' => 'PRODUCTO', 'nombre' => 'Platos Fuertes', 'desc' => 'Platos principales'],
                ['tipo' => 'PRODUCTO', 'nombre' => 'Bebidas', 'desc' => 'Refrescos y jugos'],
                ['tipo' => 'INSUMO', 'nombre' => 'Materia Prima', 'desc' => 'Ingredientes básicos']
            ];
            $s4 = $conexion->prepare("INSERT INTO categorias (empresa_id, tipo, nombre, descripcion) VALUES (?,?,?,?)");
            foreach ($categoriasDefault as $cat) {
                $s4->execute([$e->id, $cat['tipo'], $cat['nombre'], $cat['desc']]);
            }
            echo "- Categorias inicializadas.\n";
        }
    }
    echo "PROCESO COMPLETADO.\n";

}
catch (PDOException $e) {
    echo "Error de BD: " . $e->getMessage() . "\n";
}
