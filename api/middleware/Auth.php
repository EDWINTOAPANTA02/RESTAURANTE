<?php
/**
 * Middleware de autenticación
 * Verifica que el usuario tenga sesión activa.
 * Retorna el objeto de sesión o termina con 401.
 */
function requireAuth(): object
{
    comenzarSesionSegura();
    $usuario = $_SESSION['usuario'] ?? null;
    if (!$usuario) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado. Inicia sesión.']);
        exit;
    }
    return $usuario;
}
