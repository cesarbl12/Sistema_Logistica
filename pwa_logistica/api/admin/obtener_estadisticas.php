<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

requireRole(['admin']);

try {
    $eventos = $pdo->query("SELECT tipo_evento, COUNT(*) AS total FROM eventos_log GROUP BY tipo_evento")->fetchAll();
    $conteoEventos = [
        'creacion_qr' => 0,
        'lectura_qr' => 0,
        'operacion_completada' => 0,
        'mensaje_enviado' => 0,
        'mensaje_fallido' => 0,
    ];
    foreach ($eventos as $e) {
        $conteoEventos[$e['tipo_evento']] = (int) $e['total'];
    }

    $totalUsuarios = (int) $pdo->query("SELECT COUNT(*) AS total FROM usuarios")->fetch()['total'];
    $registrosPendientes = (int) $pdo->query("SELECT COUNT(*) AS total FROM registros_accesos WHERE estado = 'pendiente'")->fetch()['total'];

    echo json_encode([
        'success' => true,
        'eventos' => $conteoEventos,
        'total_usuarios' => $totalUsuarios,
        'registros_pendientes' => $registrosPendientes,
    ]);
} catch (\PDOException $e) {
    error_log('admin/obtener_estadisticas.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
