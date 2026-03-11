<?php
$h = "localhost"; $u = "root"; $p = ""; $port = 3307; $db = "botanero_ventas";
$c = new PDO("mysql:host=$h;port=$port;dbname=$db", $u, $p);
$c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$s = [
    "CREATE TABLE informacion_negocio(id INT(11) NOT NULL AUTO_INCREMENT, empresa_id BIGINT UNSIGNED NOT NULL, nombre VARCHAR(100), ruc VARCHAR(20), direccion VARCHAR(255), telefono VARCHAR(20), correo VARCHAR(100), mensaje_pie VARCHAR(255), impuesto DECIMAL(5,2) DEFAULT 0.00, logo VARCHAR(255), PRIMARY KEY (id), KEY empresa_id (empresa_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    "CREATE TABLE categorias(id INT(11) NOT NULL AUTO_INCREMENT, empresa_id BIGINT UNSIGNED NOT NULL, tipo ENUM(\"PRODUCTO\", \"INSUMO\") NOT NULL DEFAULT \"PRODUCTO\", nombre VARCHAR(100) NOT NULL, descripcion VARCHAR(255), PRIMARY KEY (id), KEY empresa_id (empresa_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];
foreach($s as $q) $c->exec($q);
echo "Tablas recreadas.\n";

