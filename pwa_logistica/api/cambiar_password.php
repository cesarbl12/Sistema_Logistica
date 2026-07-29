<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/auth.php';

requireAuth();
$usuario_id = usuarioActualId();

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['passActual']) || empty($input['passNueva'])) {
    echo json_encode(['success' => false, 'message' => 'Debes indicar tu contraseña actual y la nueva.']);
    exit;
}

if (strlen($input['passNueva']) < 6) {
    echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($input['passActual'], $usuario['password'])) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta.']);
        exit;
    }

    $nuevoHash = password_hash($input['passNueva'], PASSWORD_BCRYPT);
    $update = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $update->execute([$nuevoHash, $usuario_id]);
    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada con éxito.']);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor. Intenta de nuevo.']);
}
?>