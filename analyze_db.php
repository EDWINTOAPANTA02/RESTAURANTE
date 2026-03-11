<?php
$host = "localhost";
$db = "botanero_ventas";
$user = "root";
$pass = "";
$charset = 'utf8mb4';

$options = [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    \PDO::ATTR_EMULATE_PREPARES => false,
];
$dsn = "mysql:host=$host;port=3307;dbname=$db;charset=$charset";
try {
    $pdo = new \PDO($dsn, $user, $pass, $options);
}
catch (\PDOException $e) {
    die(json_encode(["error" => $e->getMessage()]));
}

$tables = [
    'usuarios',
    'categorias',
    'insumos',
    'ventas',
    'insumos_venta',
    'pedidos',
    'detalles_pedidos',
    'informacion_negocio'
];

$report = [];

foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        $report[$t]['columns'] = $stmt->fetchAll();

        $stmt = $pdo->query("SHOW INDEX FROM $t");
        $report[$t]['indexes'] = $stmt->fetchAll();

    }
    catch (\Exception $e) {
        $report[$t]['error'] = $e->getMessage();
    }
}

file_put_contents('analyze_db.json', json_encode($report, JSON_PRETTY_PRINT));
echo "DONE";
