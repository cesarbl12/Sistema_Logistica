<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

requireRole(['admin']);

$tipo  = $_GET['tipo'] ?? '';
$limit = min((int) ($_GET['limit'] ?? 100), 500);

$tiposValidos = ['creacion_qr', 'lectura_qr', 'operacion_completada', 'mensaje_enviado', 'mensaje_fallido', 'edicion_registro', 'eliminacion_registro'];

try {
    $sql = "SELECT e.id, e.tipo_evento, e.detalle, e.fecha, e.registro_id, e.usuario_id,
                   r.codigo_qr, r.num_contenedor, u.nombre AS usuario_nombre
            FROM eventos_log e
            LEFT JOIN registros_accesos r ON e.registro_id = r.id
            LEFT JOIN usuarios u ON e.usuario_id = u.id";

    $params = [];
    if (!empty($tipo) && in_array($tipo, $tiposValidos, true)) {
        $sql .= " WHERE e.tipo_evento = ?";
        $params[] = $tipo;
    }

    $sql .= " ORDER BY e.fecha DESC LIMIT " . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'eventos' => $stmt->fetchAll()]);
} catch (\PDOException $e) {
    error_log('admin/obtener_bitacora.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
