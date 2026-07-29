<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/auth.php';

requireRole(['cliente']);
$cliente_id = usuarioActualId();

try {
    $stmt = $pdo->prepare("SELECT id, nombre_operador, identificacion_foto, placas_unidad, tipo_operacion, num_contenedor, linea_transporte, correo_cliente, estado, codigo_qr
                           FROM registros_accesos WHERE cliente_id = ? ORDER BY fecha_registro DESC");
    $stmt->execute([$cliente_id]);
    echo json_encode($stmt->fetchAll());
} catch (\PDOException $e) {
    error_log('obtener_registros.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>