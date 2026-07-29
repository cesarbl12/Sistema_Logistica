<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/auth.php';

requireRole(['cliente']);
$cliente_id = usuarioActualId();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int) ($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Registro no válido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM registros_accesos WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$id, $cliente_id]);
    $registro = $stmt->fetch();

    if (!$registro) {
        echo json_encode(['success' => false, 'error' => 'Registro no encontrado.']);
        exit;
    }

    if ($registro['estado'] !== 'pendiente') {
        echo json_encode(['success' => false, 'error' => 'Este registro ya fue completado y no se puede eliminar.']);
        exit;
    }

    // Registramos el evento antes de borrar, para conservar el detalle en la bitácora
    registrarEvento($pdo, 'eliminacion_registro', null, $cliente_id,
        "El cliente eliminó el registro del contenedor #{$registro['num_contenedor']} (QR {$registro['codigo_qr']}).");

    $delete = $pdo->prepare("DELETE FROM registros_accesos WHERE id = ? AND cliente_id = ? AND estado = 'pendiente'");
    $delete->execute([$id, $cliente_id]);

    $fotoPath = '../' . $registro['identificacion_foto'];
    if (is_file($fotoPath)) {
        unlink($fotoPath);
    }

    echo json_encode(['success' => true, 'message' => 'Registro eliminado.']);
} catch (\PDOException $e) {
    error_log('eliminar_registro.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
