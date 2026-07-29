<?php
/**
 * Funciones auxiliares de autenticación y control de acceso por rol.
 * Debe incluirse (require_once) en cualquier endpoint que necesite
 * saber quién es el usuario actual o restringir el acceso.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Corta la ejecución con 401 si no hay una sesión iniciada.
 */
function requireAuth(): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para continuar.']);
        exit;
    }
}

/**
 * Corta la ejecución con 401 (sin sesión) o 403 (rol no autorizado)
 * si el usuario actual no tiene uno de los roles permitidos.
 *
 * @param string[] $rolesPermitidos p. ej. ['admin'] o ['cliente', 'admin']
 */
function requireRole(array $rolesPermitidos): void {
    requireAuth();
    if (!in_array($_SESSION['rol'] ?? '', $rolesPermitidos, true)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No tienes permiso para realizar esta acción.']);
        exit;
    }
}

/**
 * ID del usuario autenticado actualmente (0 si no hay sesión).
 */
function usuarioActualId(): int {
    return (int) ($_SESSION['usuario_id'] ?? 0);
}

/**
 * Rol del usuario autenticado actualmente ('' si no hay sesión).
 */
function usuarioActualRol(): string {
    return (string) ($_SESSION['rol'] ?? '');
}
?>
