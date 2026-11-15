<?php
/**
 * Middleware Tenant (multitenant)
 * Valida que la empresa de la sesión exista y esté ACTIVA.
 * Retorna el objeto empresa o termina con 403.
 *
 * REGLA DE ORO: empresa_id SIEMPRE viene de la sesión del servidor.
 *               NUNCA confiar en empresa_id enviado desde el frontend.
 */
function requireTenant(): object
{
    $usuario = requireAuth();

    if (empty($usuario->empresa_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin empresa asignada.']);
        exit;
    }

    $bd = conectarBaseDatos();
    $stmt = $bd->prepare(
        "SELECT id, razon_social, plan, estado, ambiente, fecha_vencimiento
         FROM empresas
         WHERE id = ?
         LIMIT 1"
    );
    $stmt->execute([$usuario->empresa_id]);
    $empresa = $stmt->fetchObject();

    if (!$empresa) {
        http_response_code(403);
        echo json_encode(['error' => 'Empresa no encontrada.']);
        exit;
    }

    if ($empresa->estado !== 'ACTIVO' && $usuario->rol !== 'SUPERADMIN') {
        http_response_code(403);
        echo json_encode([
            'error' => 'Tu cuenta está ' . $empresa->estado . '. Renueva tu licencia para continuar.',
            'expirado' => true
        ]);
        exit;
    }

    // Validar vencimiento (ignorar para SuperAdmin)
    if ($usuario->rol !== 'SUPERADMIN' && !empty($empresa->fecha_vencimiento)) {
        $hoy = date('Y-m-d');
        if ($empresa->fecha_vencimiento < $hoy) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Tu suscripción expiró el ' . $empresa->fecha_vencimiento . '. Por favor, realiza tu pago mensual para continuar.',
                'expirado' => true
            ]);
            exit;
        }
    }

    return $empresa;
}
