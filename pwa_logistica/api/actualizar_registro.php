<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/auth.php';
require_once '../config/uploads.php';

requireRole(['cliente']);
$cliente_id = usuarioActualId();

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Registro no válido.']);
    exit;
}

$nombre_operador  = $_POST['nombre_operador'] ?? '';
$placas_unidad    = $_POST['placas_unidad'] ?? '';
$tipo_operacion   = $_POST['tipo_operacion'] ?? '';
$num_contenedor   = $_POST['num_contenedor'] ?? '';
$linea_transporte = $_POST['linea_transporte'] ?? '';
$correo_cliente = $_POST['correo_cliente'] ?? '';

if (empty($nombre_operador) || empty($placas_unidad) || empty($tipo_operacion) || empty($num_contenedor) || empty($linea_transporte) || empty($correo_cliente)) {
    echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios.']);
    exit;
}

if (!filter_var($correo_cliente, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'El correo del cliente no es válido.']);
    exit;
}

try {
    // Verificamos que el registro exista, pertenezca al cliente y siga pendiente
    $stmt = $pdo->prepare("SELECT * FROM registros_accesos WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$id, $cliente_id]);
    $registro = $stmt->fetch();

    if (!$registro) {
        echo json_encode(['success' => false, 'error' => 'Registro no encontrado.']);
        exit;
    }

    if ($registro['estado'] !== 'pendiente') {
        echo json_encode(['success' => false, 'error' => 'Este registro ya fue completado y no se puede modificar.']);
        exit;
    }

    $foto_path = $registro['identificacion_foto'];

    // Si el cliente subió una nueva foto, la reemplazamos
    if (isset($_FILES['identificacion']) && $_FILES['identificacion']['error'] !== UPLOAD_ERR_NO_FILE) {
        $subida = guardarImagenSubida($_FILES['identificacion']);
        if (!$subida['success']) {
            echo json_encode(['success' => false, 'error' => $subida['error']]);
            exit;
        }

        $fotoAnterior = __DIR__ . '/../' . $registro['identificacion_foto'];
        if (is_file($fotoAnterior)) {
            unlink($fotoAnterior);
        }

        $foto_path = $subida['path'];
    }

    $update = $pdo->prepare("UPDATE registros_accesos
        SET nombre_operador = ?, identificacion_foto = ?, placas_unidad = ?, tipo_operacion = ?, num_contenedor = ?, linea_transporte = ?, correo_cliente = ?
        WHERE id = ? AND cliente_id = ? AND estado = 'pendiente'");
    $update->execute([$nombre_operador, $foto_path, $placas_unidad, $tipo_operacion, $num_contenedor, $linea_transporte, $correo_cliente, $id, $cliente_id]);

    registrarEvento($pdo, 'edicion_registro', $id, $cliente_id,
        "El cliente editó el registro del contenedor #{$num_contenedor} (QR {$registro['codigo_qr']}).");

    echo json_encode(['success' => true, 'message' => 'Registro actualizado con éxito.']);
} catch (\PDOException $e) {
    error_log('actualizar_registro.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
