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
        "SELECT id, razon_social, plan, estado, ambiente
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

    if ($empresa->estado !== 'ACTIVO') {
        http_response_code(403);
        echo json_encode([
            'error' => 'Tu empresa está ' . $empresa->estado . '. Contacta al administrador del sistema.'
        ]);
        exit;
    }

    return $empresa;
}

/**
 * Devuelve el empresa_id del usuario en sesión.
 * Uso: $eid = getEmpresaId();
 */
function getEmpresaId(): int
{
    $usuario = $_SESSION['usuario'] ?? null;
    if (!$usuario || empty($usuario->empresa_id)) {
        http_response_code(401);
        echo json_encode(['error' => 'Sin sesión activa.']);
        exit;
    }
    return (int)$usuario->empresa_id;
}

/**
 * Devuelve el usuario_id del usuario en sesión.
 */
function getUsuarioId(): int
{
    $usuario = $_SESSION['usuario'] ?? null;
    if (!$usuario || empty($usuario->id)) {
        http_response_code(401);
        echo json_encode(['error' => 'Sin sesión activa.']);
        exit;
    }
    return (int)$usuario->id;
}
