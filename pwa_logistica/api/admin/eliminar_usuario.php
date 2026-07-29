<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

requireRole(['admin']);

$input = json_decode(file_get_contents('php://input'), true);
$id = (int) ($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de usuario no válido.']);
    exit;
}

if ($id === usuarioActualId()) {
    echo json_encode(['success' => false, 'error' => 'No puedes eliminar tu propia cuenta mientras tienes la sesión iniciada.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        echo json_encode(['success' => false, 'error' => 'El usuario no existe.']);
        exit;
    }

    if ($usuario['rol'] === 'admin') {
        $countAdmins = $pdo->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin'")->fetch();
        if ((int) $countAdmins['total'] <= 1) {
            echo json_encode(['success' => false, 'error' => 'No puedes eliminar al único administrador restante.']);
            exit;
        }
    }

    $delete = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $delete->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Usuario eliminado.']);
} catch (\PDOException $e) {
    error_log('admin/eliminar_usuario.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
