<?php
header('Content-Type: application/json');
require_once '../config/auth.php';

if (empty($_SESSION['usuario_id'])) {
    // 200 a propósito: "sin sesión" es una respuesta válida de este endpoint de
    // consulta de estado, no un error. Así no se marca como fallo en DevTools.
    echo json_encode(['success' => false, 'error' => 'No hay sesión activa.']);
    exit;
}

echo json_encode([
    'success' => true,
    'usuario' => [
        'id'     => (int) $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'email'  => $_SESSION['usuario_email'],
        'rol'    => $_SESSION['rol'],
    ],
]);
?>
