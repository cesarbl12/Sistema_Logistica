<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

requireRole(['admin']);

$input = json_decode(file_get_contents('php://input'), true);

$nombre   = trim($input['nombre'] ?? '');
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$rol      = $input['rol'] ?? '';

$rolesValidos = ['cliente', 'operador', 'admin'];

if (empty($nombre) || empty($email) || empty($password) || !in_array($rol, $rolesValidos, true)) {
    echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios y el rol debe ser válido.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'El correo no tiene un formato válido.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ya existe un usuario con ese correo.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $email, $hash, $rol]);

    echo json_encode(['success' => true, 'message' => 'Usuario creado con éxito.', 'id' => (int) $pdo->lastInsertId()]);
} catch (\PDOException $e) {
    error_log('admin/crear_usuario.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
