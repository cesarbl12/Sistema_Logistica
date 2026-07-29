<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/auth.php';

requireRole(['operador']);

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(['success' => false, 'error' => 'Código QR no proporcionado.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre_operador, identificacion_foto, placas_unidad, tipo_operacion, 
                                   num_contenedor, linea_transporte, correo_cliente, estado, codigo_qr, fecha_registro
                            FROM registros_accesos WHERE codigo_qr = ?");
    $stmt->execute([$codigo]);
    $registro = $stmt->fetch();

    if ($registro) {
        registrarEvento($pdo, 'lectura_qr', (int) $registro['id'], usuarioActualId(),
            "El guardia escaneó el QR {$registro['codigo_qr']} del contenedor #{$registro['num_contenedor']}.");
        echo json_encode(['success' => true, 'registro' => $registro]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontró ningún registro con este código QR.']);
    }
} catch (\PDOException $e) {
    error_log('obtener_registro.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
