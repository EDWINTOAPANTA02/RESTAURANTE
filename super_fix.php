<?php
$h = "localhost";
$u = "root";
$p = "";
$port = 3307;
$db = "botanero_ventas";
try {
    $c = new PDO("mysql:host=$h;port=$port;dbname=$db", $u, $p);
    $c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "SUPER FIX INICIADO...\n";
    $c->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Recreando informacion_negocio...\n";
    $c->exec("DROP TABLE IF EXISTS informacion_negocio");
    $c->exec("CREATE TABLE informacion_negocio (
      id int(11) NOT NULL AUTO_INCREMENT,
      empresa_id bigint(20) unsigned NOT NULL,
      nombre varchar(100) NOT NULL,
      ruc varchar(20) NOT NULL,
      direccion varchar(255) DEFAULT NULL,
      telefono varchar(20) DEFAULT NULL,
      correo varchar(100) DEFAULT NULL,
      mensaje_pie varchar(255) DEFAULT NULL,
      impuesto decimal(5,2) DEFAULT 0.00,
      logo varchar(255) DEFAULT NULL,
      PRIMARY KEY (id),
      KEY empresa_id (empresa_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Recreando categorias...\n";
    $c->exec("DROP TABLE IF EXISTS categorias");
    $c->exec("CREATE TABLE categorias (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      empresa_id bigint(20) unsigned NOT NULL,
      tipo enum('PRODUCTO','INSUMO') NOT NULL DEFAULT 'PRODUCTO',
      nombre varchar(100) NOT NULL,
      descripcion varchar(255) DEFAULT NULL,
      PRIMARY KEY (id),
      KEY empresa_id (empresa_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Recuperando datos de empresas...\n";
    $empresas = $c->query("SELECT id, razon_social, ruc, correo, telefono, direccion_matriz FROM empresas")->fetchAll(PDO::FETCH_OBJ);
    foreach ($empresas as $e) {
        // Info Negocio
        $s3 = $c->prepare("INSERT INTO informacion_negocio (empresa_id, nombre, ruc, direccion, telefono, correo) VALUES (?,?,?,?,?,?)");
        $s3->execute([$e->id, $e->razon_social, $e->ruc, $e->direccion_matriz, $e->telefono, $e->correo]);

        // Categorias Default
        $categoriasDefault = [
            ['tipo' => 'PRODUCTO', 'nombre' => 'Entradas', 'desc' => 'Platos de entrada'],
            ['tipo' => 'PRODUCTO', 'nombre' => 'Platos Fuertes', 'desc' => 'Platos principales'],
            ['tipo' => 'PRODUCTO', 'nombre' => 'Bebidas', 'desc' => 'Refrescos y jugos'],
            ['tipo' => 'INSUMO', 'nombre' => 'Materia Prima', 'desc' => 'Ingredientes básicos']
        ];
        $s4 = $c->prepare("INSERT INTO categorias (empresa_id, tipo, nombre, descripcion) VALUES (?,?,?,?)");
        foreach ($categoriasDefault as $cat) {
            $s4->execute([$e->id, $cat['tipo'], $cat['nombre'], $cat['desc']]);
        }
        echo "Empresa {$e->id} inicializada OK.\n";
    }

    $c->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "SUPER FIX COMPLETADO.\n";

}
catch (PDOException $e) {
    echo "Error de BD: " . $e->getMessage() . "\n";
}
