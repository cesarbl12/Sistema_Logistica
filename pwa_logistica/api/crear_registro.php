<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/auth.php';
require_once '../config/uploads.php';

requireRole(['cliente']);
$cliente_id = usuarioActualId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_operador  = $_POST['nombre_operador'] ?? '';
    $placas_unidad    = $_POST['placas_unidad'] ?? '';
    $tipo_operacion   = $_POST['tipo_operacion'] ?? '';
    $num_contenedor   = $_POST['num_contenedor'] ?? '';
    $linea_transporte = $_POST['linea_transporte'] ?? '';
    $correo_cliente = $_POST['correo_cliente'] ?? '';

    if (empty($correo_cliente)) {
        echo json_encode(['success' => false, 'error' => 'El correo del cliente es obligatorio.']);
        exit;
    }

    if (!filter_var($correo_cliente, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'El correo del cliente no es válido.']);
        exit;
    }

    $subida = guardarImagenSubida($_FILES['identificacion'] ?? []);
    if (!$subida['success']) {
        echo json_encode(['success' => false, 'error' => $subida['error']]);
        exit;
    }
    $foto_path = $subida['path'];

    $codigo_qr = 'REG-' . strtoupper(uniqid());

    try {
        $stmt = $pdo->prepare("INSERT INTO registros_accesos
            (cliente_id, nombre_operador, identificacion_foto, placas_unidad, tipo_operacion, num_contenedor, linea_transporte, correo_cliente, codigo_qr)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$cliente_id, $nombre_operador, $foto_path, $placas_unidad, $tipo_operacion, $num_contenedor, $linea_transporte, $correo_cliente, $codigo_qr]);
        $registroId = (int) $pdo->lastInsertId();

        registrarEvento($pdo, 'creacion_qr', $registroId, $cliente_id,
            "Se generó el QR {$codigo_qr} para el contenedor #{$num_contenedor} (operador: {$nombre_operador}).");

        echo json_encode(['success' => true, 'codigo_qr' => $codigo_qr]);
    } catch (\PDOException $e) {
        error_log('crear_registro.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
    }
}
?>