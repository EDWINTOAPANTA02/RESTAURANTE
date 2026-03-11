const http = require('http');

const data = JSON.stringify({
    idMesa: 1,
    cliente_id: null,
    tipo_factura: 'CONSUMIDOR_FINAL',
    total: 100,
    pagado: 100,
    idUsuario: 1,
    insumos: [{ id: 1, precio: 100, cantidad: 1 }],
    atiende: 'Test'
});

const options = {
    hostname: 'localhost',
    port: 80,
    path: '/botanero_ventas/api/registrar_venta.php',
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Content-Length': data.length
        // Not passing session cookie, so wait, it will fail at requireAuth()!
    }
};

const req = http.request(options, (res) => {
    let raw = '';
    res.on('data', (d) => raw += d);
    res.on('end', () => {
        console.log("STATUS CODE:", res.statusCode);
        console.log("RESPONSE BODY:", raw);
    });
});

req.on('error', (error) => {
    console.error(error);
});

req.write(data);
req.end();
