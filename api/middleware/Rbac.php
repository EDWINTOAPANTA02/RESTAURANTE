<?php
/**
 * Middleware RBAC (control de acceso por rol)
 * Verifica que el usuario tenga uno de los roles permitidos.
 */
function requireRole(array $rolesPermitidos): void
{
    $usuario = $_SESSION['usuario'] ?? null;
    $rol = $usuario->rol ?? 'N/A';

    if (!$usuario || !in_array($rol, $rolesPermitidos, true)) {
        // Log de intento de acceso no autorizado
        registrarAuditoria(
            'ACCESO_DENEGADO',
            'sesion',
            0,
        ['ruta' => $_SERVER['REQUEST_URI'] ?? '', 'rol_intentado' => $rol],
            null
        );
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permiso para realizar esta acción.']);
        exit;
    }
}
