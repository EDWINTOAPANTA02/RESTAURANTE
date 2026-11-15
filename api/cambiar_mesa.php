<?php
include_once "encabezado.php";
include_once "funciones.php";

$empresa = requireTenant();
$eid = $empresa->id;
$datos = json_decode(file_get_contents("php://input"));

if (!$datos || empty($datos->mesaOrigen) || empty($datos->mesaDestino)) {
    http_response_code(400);
    echo json_encode(["resultado" => false, "error" => "Debes indicar la mesa origen y la mesa destino."]);
    exit;
}

$origen = (int)$datos->mesaOrigen;
$destino = (int)$datos->mesaDestino;

if ($origen === $destino) {
    echo json_encode(["resultado" => false, "error" => "La mesa destino debe ser diferente a la mesa actual."]);
    exit;
}

$bd = conectarBaseDatos();

try {
    // Verificar que la mesa destino no esté ocupada
    $stmtCheck = $bd->prepare("SELECT id FROM pedidos WHERE idMesa = ? AND estado = 'ABIERTO' AND empresa_id = ? LIMIT 1");
    $stmtCheck->execute([$destino, $eid]);
    if ($stmtCheck->fetch()) {
        echo json_encode(["resultado" => false, "error" => "La Mesa #$destino ya se encuentra ocupada."]);
        exit;
    }

    $stmtUpd = $bd->prepare("UPDATE pedidos SET idMesa = ? WHERE idMesa = ? AND estado = 'ABIERTO' AND empresa_id = ?");
    $ok = $stmtUpd->execute([$destino, $origen, $eid]);

    echo json_encode(["resultado" => $ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["resultado" => false, "error" => $e->getMessage()]);
}
