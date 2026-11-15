<?php
include_once "encabezado.php";
include_once "funciones.php";

$configPath = __DIR__ . '/config.php';
$config = file_exists($configPath) ? require $configPath : require __DIR__ . '/config.example.php';

if (($config['env'] ?? 'local') === 'production') {
    http_response_code(403);
    echo json_encode(["error" => "Instalador deshabilitado en producción"]);
    exit;
}

$dbConfig = $config['db'];
$host = $dbConfig['host'];
$port = $dbConfig['port'];
$dbName = $dbConfig['name'];
$usuario = $dbConfig['user'];
$password = $dbConfig['pass'];
$resultados = [];

$crear_bd = "CREATE DATABASE IF NOT EXISTS `$dbName`";


$sentencias = [
      ["tabla" => "categorias",
            "sentencia" =>
            'CREATE TABLE IF NOT EXISTS categorias(
            id INT(11) NOT NULL AUTO_INCREMENT,
            empresa_id BIGINT UNSIGNED NOT NULL,
            tipo ENUM("PLATILLO", "BEBIDA") NOT NULL DEFAULT "PLATILLO",
            nombre VARCHAR(100) NOT NULL,
            descripcion VARCHAR(255),
            PRIMARY KEY (id),
            KEY empresa_id (empresa_id)
  	);'],


      ["tabla" => "insumos",
            "sentencia" =>
            'CREATE TABLE IF NOT EXISTS insumos(
            id INT(11) NOT NULL AUTO_INCREMENT,
            empresa_id BIGINT UNSIGNED NOT NULL,
            codigo VARCHAR(100) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            precio DECIMAL(10,2) NOT NULL,
            tipo ENUM("PLATILLO", "BEBIDA") NOT NULL,
            categoria INT(11) NOT NULL,
            PRIMARY KEY (id),
            KEY empresa_id (empresa_id)
  		);'],

      ["tabla" => "informacion_negocio",
            "sentencia" => 'CREATE TABLE IF NOT EXISTS informacion_negocio(
            id INT(11) NOT NULL AUTO_INCREMENT,
            empresa_id BIGINT UNSIGNED NOT NULL,
            nombre VARCHAR(100),
            ruc VARCHAR(20),
            direccion VARCHAR(255),
            telefono VARCHAR(20),
            correo VARCHAR(100),
            mensaje_pie VARCHAR(255),
            impuesto DECIMAL(5,2) DEFAULT 0.00,
            logo VARCHAR(255),
            PRIMARY KEY (id),
            KEY empresa_id (empresa_id)
 		);'],

      ["tabla" => "usuarios",
            "sentencia" =>
            'CREATE TABLE IF NOT EXISTS usuarios(
            id INT(11) NOT NULL AUTO_INCREMENT,
            empresa_id BIGINT UNSIGNED NOT NULL,
            correo VARCHAR(100) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            telefono VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            rol ENUM("SUPERADMIN", "ADMINISTRADOR", "MESERO", "COCINERO") DEFAULT "MESERO",
            PRIMARY KEY (id),
            KEY empresa_id (empresa_id)
 		);'],

      ["tabla" => "ventas",
            "sentencia" =>
            'CREATE TABLE IF NOT EXISTS ventas(
            id INT(11) NOT NULL AUTO_INCREMENT,
            empresa_id BIGINT UNSIGNED NOT NULL,
            idMesa INT(11) NOT NULL,
            cliente VARCHAR(100),
            fecha DATETIME NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            pagado DECIMAL(10,2) NOT NULL,
            idUsuario INT(11) NOT NULL,
            PRIMARY KEY (id),
            KEY empresa_id (empresa_id)
 		);'],

      ["tabla" => "insumos_venta",
            "sentencia" =>
            'CREATE TABLE IF NOT EXISTS insumos_venta(
            id INT(11) NOT NULL AUTO_INCREMENT,
            idInsumo INT(11) NOT NULL,
            precio DECIMAL(10,2) NOT NULL,
            cantidad INT NOT NULL,
            idVenta INT(11),
            PRIMARY KEY (id)
 		);'],

      ["tabla" => "configuracion_planes",
            "sentencia" =>
            'CREATE TABLE IF NOT EXISTS configuracion_planes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre_plan VARCHAR(50) NOT NULL UNIQUE,
            precio DECIMAL(10, 2) NOT NULL,
            duracion_meses INT DEFAULT 1,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 		);'],

      ["tabla" => "pagos_empresa",
            "sentencia" =>
            'CREATE TABLE IF NOT EXISTS pagos_empresa (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empresa_id BIGINT UNSIGNED NOT NULL,
            monto DECIMAL(10,2) NOT NULL,
            metodo_pago VARCHAR(50) DEFAULT "PAYPAL",
            referencia VARCHAR(100),
            fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY empresa_id (empresa_id)
 		);']
];

$conexion = new PDO("mysql:host=$host;port=$port", $usuario, $password);
$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$creada_bd = $conexion->exec($crear_bd);
if ($creada_bd)
      array_push($resultados, "Base de datos creada correctamente");
$conexion->exec("USE `$dbName`");
#Ahora crear las tablas
//$conexion->close();

$conexion = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $usuario, $password);
$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

foreach ($sentencias as $sentencia) {
      $conexion->exec($sentencia["sentencia"]);

      array_push($resultados, "Tabla {$sentencia["tabla"]} creada correctamente");

}

$conexion = null;
echo json_encode($resultados);
