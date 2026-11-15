<?php
include_once "encabezado.php";
include_once "funciones.php";
requireTenant();

$eid = getEmpresaId();
$informacion = obtenerInformacionLocal();

if (count($informacion) < 1) {
    $bd = conectarBaseDatos();
    $stmtEmp = $bd->prepare("SELECT id, razon_social, ruc, telefono, direccion_matriz, correo FROM empresas WHERE id = ? LIMIT 1");
    $stmtEmp->execute([$eid]);
    $emp = $stmtEmp->fetchObject();

    $informacion = [
        "empresa_id" => $eid,
        "nombre" => $emp ? $emp->razon_social : "Restaurante",
        "ruc" => $emp ? ($emp->ruc ?? "") : "",
        "telefono" => $emp ? ($emp->telefono ?? "") : "",
        "direccion" => $emp ? ($emp->direccion_matriz ?? "") : "",
        "correo" => $emp ? ($emp->correo ?? "") : "",
        "numeroMesas" => "5",
        "logo" => "/fotos/logo.png"
    ];
} else {
    $informacion = $informacion[0];
    if (!isset($informacion->empresa_id)) {
        $informacion->empresa_id = $eid;
    }
}
echo json_encode($informacion);
