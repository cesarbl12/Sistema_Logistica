<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/auth.php';

const LOGIN_MAX_INTENTOS = 5;
const LOGIN_VENTANA_MINUTOS = 15;

$input = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email'] ?? '');
$password = (string) ($input['password'] ?? '');

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ingresa tu correo y contraseña.']);
    exit;
}

// Combina correo + IP: frena la fuerza bruta contra una cuenta puntual
// sin bloquear a todos los usuarios que comparten IP (oficina, NAT, etc.).
$identificador = strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'desconocida');

try {
    $stmtIntentos = $pdo->prepare(
        "SELECT COUNT(*) FROM intentos_login WHERE identificador = ? AND fecha > (NOW() - INTERVAL ? MINUTE)"
    );
    $stmtIntentos->execute([$identificador, LOGIN_VENTANA_MINUTOS]);
    if ((int) $stmtIntentos->fetchColumn() >= LOGIN_MAX_INTENTOS) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Demasiados intentos fallidos. Intenta de nuevo en unos minutos.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password'])) {
        $registrarIntento = $pdo->prepare("INSERT INTO intentos_login (identificador) VALUES (?)");
        $registrarIntento->execute([$identificador]);

        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Correo o contraseña incorrectos.']);
        exit;
    }

    $pdo->prepare("DELETE FROM intentos_login WHERE identificador = ?")->execute([$identificador]);

    // Regeneramos el ID de sesión al iniciar sesión para evitar fijación de sesión.
    session_regenerate_id(true);

    $_SESSION['usuario_id']     = (int) $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_email']  = $usuario['email'];
    $_SESSION['rol']            = $usuario['rol'];

    echo json_encode([
        'success' => true,
        'usuario' => [
            'id'     => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email'  => $usuario['email'],
            'rol'    => $usuario['rol'],
        ],
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
