<?php
/**
 * Endpoint: emitir_factura.php
 * - Emite comprobante electrónico SRI firmado (.p12 / XAdES-BES)
 * - Transmite al Web Service SOAP oficial del SRI (Recepción + Autorización)
 * - Gestiona el certificado de firma electrónica (.p12) de la empresa
 */
include_once "encabezado.php";
include_once "funciones.php";
include_once "middleware/Auth.php";
include_once "middleware/Tenant.php";
include_once "middleware/Rbac.php";

$usuario = requireAuth();
$empresa = requireTenant();
requireRole(['ADMINISTRADOR', 'CAJERO', 'MESERO']);

$datos = json_decode(file_get_contents("php://input"));
$empresaId = getEmpresaId();
$accion = $datos->accion ?? '';

// 1. Consultar estado del certificado .p12 y ambiente SRI
if ($accion === 'estado_certificado') {
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT ruta_archivo, fecha_expira, activo FROM certificados_digitales WHERE empresa_id = ? LIMIT 1");
    $stmt->execute([$empresaId]);
    $cert = $stmt->fetchObject();
    echo json_encode([
        'resultado' => true,
        'ambiente' => (int)($empresa->ambiente ?? 1),
        'ruc' => $empresa->ruc ?? '',
        'razon_social' => $empresa->razon_social ?? '',
        'certificado' => $cert ?: null
    ]);
    exit;
}

// 2. Subir y validar certificado .p12 (solo ADMINISTRADOR)
if ($accion === 'subir_certificado') {
    requireRole(['ADMINISTRADOR']);
    if (empty($datos->archivo_base64) || !isset($datos->password)) {
        echo json_encode(['resultado' => false, 'error' => 'Debes seleccionar el archivo .p12 e ingresar su contraseña.']);
        exit;
    }
    $ambiente = isset($datos->ambiente) ? (int)$datos->ambiente : 1;
    $res = guardarCertificadoP12($empresaId, $datos->archivo_base64, $datos->password, $ambiente);
    echo json_encode($res);
    exit;
}

// 3. Descargar XML o consultar detalle de factura
if (!empty($datos->factura_id)) {
    $factura = obtenerFacturaPorId((int)$datos->factura_id, $empresaId);
    if (!$factura) {
        http_response_code(404);
        echo json_encode(["resultado" => false, "error" => "Factura no encontrada."]);
        exit;
    }
    if (!empty($datos->enviar_sri)) {
        echo json_encode(transmitirFacturaSri((int)$factura->id, $empresaId));
        exit;
    }
    $factura->detalles = obtenerDetallesFactura((int)$datos->factura_id, $empresaId);
    echo json_encode(["resultado" => true, "factura" => $factura]);
    exit;
}

// 4. Emitir desde una venta y opcionalmente transmitir al WS SOAP del SRI
if (empty($datos->venta_id)) {
    http_response_code(400);
    echo json_encode(["resultado" => false, "error" => "Se requiere el ID de la venta (venta_id)."]);
    exit;
}

$emision = emitirFacturaDesdeVenta((int)$datos->venta_id, $empresaId, (int)$usuario->id);
if (!$emision['resultado']) {
    echo json_encode($emision);
    exit;
}

if (!empty($datos->enviar_sri) && !empty($emision['factura']->id)) {
    $sriRes = transmitirFacturaSri((int)$emision['factura']->id, $empresaId);
    echo json_encode($sriRes);
    exit;
}

echo json_encode($emision);
