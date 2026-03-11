<?php
$h = "localhost";
$u = "root";
$p = "";
$port = 3307;
$db = "botanero_ventas";
try {
    $c = new PDO("mysql:host=$h;port=$port;dbname=$db", $u, $p);
    $c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Asegurando columnas en informacion_negocio...\n";
    $cols_info = $c->query("SHOW COLUMNS FROM informacion_negocio")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('id', $cols_info)) {
        $c->exec("ALTER TABLE informacion_negocio ADD COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
    }
    else {
        // Try to set auto_increment if not set
        try {
            $c->exec("ALTER TABLE informacion_negocio MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT");
        }
        catch (Exception $e) {
        }
    }
    if (!in_array('empresa_id', $cols_info)) {
        $c->exec("ALTER TABLE informacion_negocio ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id");
    }
    if (!in_array('ruc', $cols_info)) {
        $c->exec("ALTER TABLE informacion_negocio ADD COLUMN ruc VARCHAR(20) AFTER nombre");
    }
    if (!in_array('direccion', $cols_info)) {
        $c->exec("ALTER TABLE informacion_negocio ADD COLUMN direccion VARCHAR(255) AFTER ruc");
    }
    if (!in_array('correo', $cols_info)) {
        $c->exec("ALTER TABLE informacion_negocio ADD COLUMN correo VARCHAR(100) AFTER telefono");
    }

    echo "Asegurando columnas en categorias...\n";
    $cols_cat = $c->query("SHOW COLUMNS FROM categorias")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('empresa_id', $cols_cat)) {
        $c->exec("ALTER TABLE categorias ADD COLUMN empresa_id BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER id");
    }
    try {
        $c->exec("ALTER TABLE categorias MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT");
    }
    catch (Exception $e) {
    }

    echo "SCHEMA FIX COMPLETED.\n";

}
catch (PDOException $e) {
    echo "Error de BD: " . $e->getMessage() . "\n";
}
