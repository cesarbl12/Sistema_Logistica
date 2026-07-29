<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../../config/auth.php';

requireRole(['admin']);

try {
    $stmt = $pdo->query("SELECT id, nombre, email, rol, creado_en FROM usuarios ORDER BY creado_en DESC");
    echo json_encode(['success' => true, 'usuarios' => $stmt->fetchAll()]);
} catch (\PDOException $e) {
    error_log('admin/listar_usuarios.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
