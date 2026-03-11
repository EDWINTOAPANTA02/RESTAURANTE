<?php
// MOCK Auth and test payload
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION['usuario'] = (object)['rol' => 'ADMINISTRADOR', 'id' => 1];

// Include all required files directly to mock the environment
include_once 'c:\xampp\htdocs\botanero_ventas\api\funciones.php';
// Mock getEmpresaId
function getEmpresaId()
{
    return 1;
}

$venta = json_decode('{
    "idMesa": 1,
    "cliente_id": null,
    "tipo_factura": "CONSUMIDOR_FINAL",
    "total": 100,
    "pagado": 100,
    "idUsuario": 1,
    "insumos": [{"id": 1, "precio": 100, "cantidad": 1}],
    "atiende": "Test"
}');

$venta->empresa_id = getEmpresaId();

$resultado = registrarVenta($venta);
var_dump($resultado);
