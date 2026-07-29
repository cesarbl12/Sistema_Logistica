<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

requireRole(['admin']);

$input = json_decode(file_get_contents('php://input'), true);

$id       = (int) ($input['id'] ?? 0);
$nombre   = trim($input['nombre'] ?? '');
$email    = trim($input['email'] ?? '');
$rol      = $input['rol'] ?? '';
$password = $input['password'] ?? ''; // opcional, solo si se quiere cambiar

$rolesValidos = ['cliente', 'operador', 'admin'];

if ($id <= 0 || empty($nombre) || empty($email) || !in_array($rol, $rolesValidos, true)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos o inválidos.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'El correo no tiene un formato válido.']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $check->execute([$email, $id]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ya existe otro usuario con ese correo.']);
        exit;
    }

    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.']);
            exit;
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ?, password = ? WHERE id = ?");
        $stmt->execute([$nombre, $email, $rol, $hash, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?");
        $stmt->execute([$nombre, $email, $rol, $id]);
    }

    echo json_encode(['success' => true, 'message' => 'Usuario actualizado con éxito.']);
} catch (\PDOException $e) {
    error_log('admin/actualizar_usuario.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
