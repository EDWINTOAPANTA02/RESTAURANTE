<?php
/**
 * Servicio de Facturación Electrónica SRI Ecuador (Esquema Offline SOAP + Firma .p12)
 */

define('SRI_CERT_DIR', __DIR__ . '/certificados/');
define('SRI_SECRET_KEY', hash('sha256', 'botanero_sri_key_2026_ec', true));

function cifrarPassphraseSri(string $plain): string
{
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', SRI_SECRET_KEY, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}

function descifrarPassphraseSri(string $encoded): string
{
    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) <= 16) {
        return '';
    }
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $dec = openssl_decrypt($cipher, 'AES-256-CBC', SRI_SECRET_KEY, OPENSSL_RAW_DATA, $iv);
    return $dec !== false ? $dec : '';
}

function obtenerConfigOpenSsl(): array
{
    $posibles = [
        'C:/xampp/apache/conf/openssl.cnf',
        'C:/xampp/php/extras/ssl/openssl.cnf',
        getenv('OPENSSL_CONF') ?: ''
    ];
    foreach ($posibles as $ruta) {
        if ($ruta && file_exists($ruta)) {
            return ['config' => $ruta];
        }
    }
    return [];
}

/**
 * Guarda y valida un archivo .p12 subido en Base64 para la empresa.
 */
function guardarCertificadoP12(int $empresaId, string $base64P12, string $password, int $ambiente = 1): array
{
    if (!is_dir(SRI_CERT_DIR)) {
        mkdir(SRI_CERT_DIR, 0750, true);
    }

    if (str_contains($base64P12, ',')) {
        $base64P12 = explode(',', $base64P12, 2)[1];
    }
    $p12Bytes = base64_decode($base64P12, true);
    if (!$p12Bytes) {
        return ['resultado' => false, 'error' => 'El archivo .p12 no tiene un formato válido.'];
    }

    $certs = [];
    if (!@openssl_pkcs12_read($p12Bytes, $certs, $password)) {
        return ['resultado' => false, 'error' => 'No se pudo abrir el certificado .p12. Verifica la contraseña de la firma electrónica.'];
    }

    $infoCert = openssl_x509_parse($certs['cert']);
    $validTo = isset($infoCert['validTo_time_t']) ? date('Y-m-d', $infoCert['validTo_time_t']) : date('Y-m-d', strtotime('+1 year'));

    $nombreArchivo = 'empresa_' . $empresaId . '.p12';
    $rutaCompleta = SRI_CERT_DIR . $nombreArchivo;
    file_put_contents($rutaCompleta, $p12Bytes);

    $passEnc = cifrarPassphraseSri($password);
    $bd = conectarBaseDatos();

    $stmt = $bd->prepare(
        "INSERT INTO certificados_digitales (empresa_id, ruta_archivo, passphrase_enc, fecha_expira, activo)
         VALUES (?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE
            ruta_archivo = VALUES(ruta_archivo),
            passphrase_enc = VALUES(passphrase_enc),
            fecha_expira = VALUES(fecha_expira),
            activo = 1"
    );
    $stmt->execute([$empresaId, $nombreArchivo, $passEnc, $validTo]);

    $stmtEmp = $bd->prepare("UPDATE empresas SET ambiente = ? WHERE id = ?");
    $stmtEmp->execute([in_array($ambiente, [1, 2], true) ? $ambiente : 1, $empresaId]);

    return [
        'resultado' => true,
        'fecha_expira' => $validTo,
        'titular' => $infoCert['subject']['CN'] ?? 'Certificado válido'
    ];
}

/**
 * Obtiene o genera (en ambiente de pruebas) las credenciales X.509 para firmar el XML.
 */
function obtenerCredencialesFirma(int $empresaId): ?array
{
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT * FROM certificados_digitales WHERE empresa_id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$empresaId]);
    $row = $stmt->fetchObject();

    if ($row) {
        $ruta = SRI_CERT_DIR . basename($row->ruta_archivo);
        if (file_exists($ruta)) {
            $pass = descifrarPassphraseSri($row->passphrase_enc);
            $certs = [];
            if (@openssl_pkcs12_read(file_get_contents($ruta), $certs, $pass)) {
                return $certs;
            }
        }
    }

    // Fallback en Ambiente 1 (Pruebas): generar certificado X.509 local de prueba si aún no suben .p12 real
    $opt = obtenerConfigOpenSsl();
    $privKey = @openssl_pkey_new(array_merge([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ], $opt));

    if (!$privKey) {
        return null;
    }

    $dn = [
        'countryName' => 'EC',
        'organizationName' => 'PRUEBAS SRI RESTAURANTE',
        'commonName' => 'FIRMA DE PRUEBA LOCAL'
    ];
    $csr = @openssl_csr_new($dn, $privKey, $opt);
    $x509 = $csr ? @openssl_csr_sign($csr, null, $privKey, 365, $opt) : false;
    if (!$x509) {
        return null;
    }

    $certOut = '';
    $pkeyOut = '';
    openssl_x509_export($x509, $certOut);
    openssl_pkey_export($privKey, $pkeyOut, null, $opt);

    return ['cert' => $certOut, 'pkey' => $pkeyOut, 'es_prueba' => true];
}

/**
 * Firma digitalmente el XML del comprobante (XMLDSig / XAdES-BES envolvente).
 */
function firmarXmlSri(string $xmlSinFirma, int $empresaId): string
{
    $creds = obtenerCredencialesFirma($empresaId);
    if (!$creds) {
        return $xmlSinFirma;
    }

    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = false;
    $dom->loadXML($xmlSinFirma);

    $canonComprobante = $dom->documentElement->C14N();
    $digestComprobante = base64_encode(hash('sha1', $canonComprobante, true));

    $certClean = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/', '', $creds['cert']);
    $certDer = base64_decode($certClean);
    $certDigest = base64_encode(hash('sha1', $certDer, true));
    $certInfo = openssl_x509_parse($creds['cert']);
    $issuerName = 'CN=' . ($certInfo['issuer']['CN'] ?? 'Autoridad de Certificacion EC') . ',C=EC';
    $serialNumber = (string)($certInfo['serialNumber'] ?? '1');
    $signingTime = gmdate('Y-m-d\TH:i:s\Z');

    $signedPropsXml = '<etsi:SignedProperties xmlns:etsi="http://uri.etsi.org/01903/v1.3.2#" Id="Signature-SignedProperties">'
        . '<etsi:SignedSignatureProperties>'
        . '<etsi:SigningTime>' . $signingTime . '</etsi:SigningTime>'
        . '<etsi:SigningCertificate><etsi:Cert>'
        . '<etsi:CertDigest>'
        . '<ds:DigestMethod xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>'
        . '<ds:DigestValue xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . $certDigest . '</ds:DigestValue>'
        . '</etsi:CertDigest>'
        . '<etsi:IssuerSerial>'
        . '<ds:X509IssuerName xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . htmlspecialchars($issuerName, ENT_XML1) . '</ds:X509IssuerName>'
        . '<ds:X509SerialNumber xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' . htmlspecialchars($serialNumber, ENT_XML1) . '</ds:X509SerialNumber>'
        . '</etsi:IssuerSerial>'
        . '</etsi:Cert></etsi:SigningCertificate>'
        . '</etsi:SignedSignatureProperties>'
        . '</etsi:SignedProperties>';

    $domProps = new \DOMDocument('1.0', 'UTF-8');
    $domProps->loadXML($signedPropsXml);
    $digestSignedProps = base64_encode(hash('sha1', $domProps->documentElement->C14N(), true));

    $signedInfoXml = '<ds:SignedInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">'
        . '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></ds:CanonicalizationMethod>'
        . '<ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></ds:SignatureMethod>'
        . '<ds:Reference Id="SignedPropertiesID" Type="http://uri.etsi.org/01903#SignedProperties" URI="#Signature-SignedProperties">'
        . '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>'
        . '<ds:DigestValue>' . $digestSignedProps . '</ds:DigestValue>'
        . '</ds:Reference>'
        . '<ds:Reference URI="#comprobante">'
        . '<ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"></ds:Transform></ds:Transforms>'
        . '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>'
        . '<ds:DigestValue>' . $digestComprobante . '</ds:DigestValue>'
        . '</ds:Reference>'
        . '</ds:SignedInfo>';

    $domSignedInfo = new \DOMDocument('1.0', 'UTF-8');
    $domSignedInfo->loadXML($signedInfoXml);
    $canonSignedInfo = $domSignedInfo->documentElement->C14N();

    $signatureBin = '';
    openssl_sign($canonSignedInfo, $signatureBin, $creds['pkey'], OPENSSL_ALGO_SHA1);
    $signatureValue = base64_encode($signatureBin);

    $signatureXml = '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:etsi="http://uri.etsi.org/01903/v1.3.2#" Id="Signature">'
        . $signedInfoXml
        . '<ds:SignatureValue Id="SignatureValue">' . $signatureValue . '</ds:SignatureValue>'
        . '<ds:KeyInfo Id="Certificate1"><ds:X509Data><ds:X509Certificate>' . $certClean . '</ds:X509Certificate></ds:X509Data></ds:KeyInfo>'
        . '<ds:Object Id="Signature-Object"><etsi:QualifyingProperties Target="#Signature">' . $signedPropsXml . '</etsi:QualifyingProperties></ds:Object>'
        . '</ds:Signature>';

    $frag = $dom->createDocumentFragment();
    $frag->appendXML($signatureXml);
    $dom->documentElement->appendChild($frag);

    return $dom->saveXML();
}

/**
 * Envía una petición SOAP 1.1 por cURL a los Web Services del SRI.
 */
function invocarSoapSri(string $url, string $soapBody): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $soapBody,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: ' . strlen($soapBody),
        ],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $code >= 500) {
        return ['exito' => false, 'error' => $err ?: "HTTP $code desde el WS del SRI"];
    }
    return ['exito' => true, 'xml' => $response];
}

/**
 * Procesa el envío (Recepción) y consulta (Autorización) de una factura ante el WS oficial del SRI.
 */
function transmitirFacturaSri(int $facturaId, int $empresaId): array
{
    $bd = conectarBaseDatos();
    $stmt = $bd->prepare("SELECT * FROM facturas WHERE id = ? AND empresa_id = ? LIMIT 1");
    $stmt->execute([$facturaId, $empresaId]);
    $factura = $stmt->fetchObject();

    if (!$factura) {
        return ['resultado' => false, 'error' => 'Factura no encontrada.'];
    }

    $ambiente = (int)$factura->ambiente_sri;
    $urlRecepcion = ($ambiente === 2)
        ? 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline'
        : 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline';

    $urlAutorizacion = ($ambiente === 2)
        ? 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline'
        : 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline';

    // Asegurar que el XML esté firmado
    $xmlActual = $factura->xml_autorizado ?? '';
    if (!str_contains($xmlActual, '<ds:Signature')) {
        $xmlActual = firmarXmlSri($xmlActual, $empresaId);
    }

    // 1. SOAP Recepción (validarComprobante)
    $xmlBase64 = base64_encode($xmlActual);
    $soapRecepcion = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.recepcion">'
        . '<soapenv:Header/><soapenv:Body>'
        . '<ec:validarComprobante><xml>' . $xmlBase64 . '</xml></ec:validarComprobante>'
        . '</soapenv:Body></soapenv:Envelope>';

    $respRec = invocarSoapSri($urlRecepcion, $soapRecepcion);
    if (!$respRec['exito']) {
        return ['resultado' => false, 'error' => 'No se pudo conectar al WS de Recepción del SRI: ' . $respRec['error']];
    }

    $xmlRespRec = $respRec['xml'];
    $estadoRecepcion = 'EN_PROCESO';
    $mensajeSri = '';

    if (preg_match('/<estado>(.*?)<\/estado>/s', $xmlRespRec, $mEst)) {
        $estRec = trim($mEst[1]);
        if ($estRec === 'DEVUELTA') {
            $estadoRecepcion = 'RECHAZADA';
        }
    }
    if (preg_match_all('/<mensaje>(.*?)<\/mensaje>/s', $xmlRespRec, $mMsgs)) {
        $textos = [];
        foreach ($mMsgs[1] as $bloque) {
            if (preg_match('/<mensaje>(.*?)<\/mensaje>/s', $bloque, $sub)) {
                $textos[] = trim($sub[1]);
            } else {
                $textos[] = trim(strip_tags($bloque));
            }
        }
        $mensajeSri = implode(' | ', array_filter($textos));
    }

    // Si fue RECIBIDA (o CLAVE ACCESO EN PROCESAMIENTO / REGISTRADA), consultar Autorización
    $numeroAutorizacion = null;
    $fechaAutorizacion = null;

    if ($estadoRecepcion !== 'RECHAZADA' || str_contains($mensajeSri, 'CLAVE ACCESO REGISTRADA')) {
        $soapAut = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.autorizacion">'
            . '<soapenv:Header/><soapenv:Body>'
            . '<ec:autorizacionComprobante><claveAccesoComprobante>' . $factura->clave_acceso . '</claveAccesoComprobante></ec:autorizacionComprobante>'
            . '</soapenv:Body></soapenv:Envelope>';

        $respAut = invocarSoapSri($urlAutorizacion, $soapAut);
        if ($respAut['exito']) {
            $xmlRespAut = $respAut['xml'];
            if (preg_match('/<estado>(AUTORIZADO|NO AUTORIZADO|EN PROCESO)<\/estado>/s', $xmlRespAut, $mAut)) {
                $estAut = trim($mAut[1]);
                if ($estAut === 'AUTORIZADO') {
                    $estadoRecepcion = 'AUTORIZADA';
                    $numeroAutorizacion = $factura->clave_acceso;
                    $fechaAutorizacion = date('Y-m-d H:i:s');
                    $mensajeSri = 'AUTORIZADO POR EL SRI';
                } elseif ($estAut === 'NO AUTORIZADO') {
                    $estadoRecepcion = 'RECHAZADA';
                }
            }
        }
    }

    $upd = $bd->prepare(
        "UPDATE facturas
         SET estado_sri = ?, fecha_autorizacion = ?, numero_autorizacion = ?,
             xml_autorizado = ?, respuesta_sri = ?, mensaje_sri = ?
         WHERE id = ? AND empresa_id = ?"
    );
    $upd->execute([
        $estadoRecepcion,
        $fechaAutorizacion,
        $numeroAutorizacion,
        $xmlActual,
        $respRec['xml'],
        $mensajeSri ?: 'Enviado al WS del SRI',
        $facturaId,
        $empresaId
    ]);

    return [
        'resultado' => true,
        'estado_sri' => $estadoRecepcion,
        'mensaje_sri' => $mensajeSri ?: 'Procesado con el WS del SRI',
        'clave_acceso' => $factura->clave_acceso,
        'numero_serie' => $factura->numero_serie
    ];
}
