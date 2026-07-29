<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/email.php';
require_once '../config/logger.php';
require_once '../config/auth.php';

requireRole(['operador']);

$input = json_decode(file_get_contents('php://input'), true);
$codigo = $input['codigo_qr'] ?? '';

if (empty($codigo)) {
    echo json_encode(['success' => false, 'error' => 'Código QR no proporcionado.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM registros_accesos WHERE codigo_qr = ?");
    $stmt->execute([$codigo]);
    $registro = $stmt->fetch();

    if (!$registro) {
        echo json_encode(['success' => false, 'error' => 'No se encontró ningún registro con este código QR.']);
        exit;
    }

    if ($registro['estado'] === 'completado') {
        echo json_encode(['success' => false, 'error' => 'Este registro ya fue marcado como completado anteriormente.']);
        exit;
    }

    $update = $pdo->prepare("UPDATE registros_accesos SET estado = 'completado' WHERE codigo_qr = ?");
    $update->execute([$codigo]);

    $tipoOperacionTexto = [
        'dejar'   => 'dejar el contenedor',
        'retirar' => 'retirar el contenedor',
        'ambos'   => 'dejar y retirar el contenedor',
    ][$registro['tipo_operacion']] ?? $registro['tipo_operacion'];

    $asunto = "Operación completada: contenedor #{$registro['num_contenedor']}";
    $cuerpo = "<p>Hola,</p>"
            . "<p>Tu operación para <strong>{$tipoOperacionTexto}</strong> #{$registro['num_contenedor']} "
            . "(unidad {$registro['placas_unidad']}, línea {$registro['linea_transporte']}) "
            . "ha sido completada por nuestro personal en la entrada.</p>"
            . "<p>¡Gracias por confiar en nosotros!</p>";

    $correo = enviarCorreo($registro['correo_cliente'], $asunto, $cuerpo);

    registrarEvento($pdo, 'operacion_completada', (int) $registro['id'], usuarioActualId(),
        "Operación completada para el contenedor #{$registro['num_contenedor']} (QR {$registro['codigo_qr']}).");

    if ($correo['success']) {
        registrarEvento($pdo, 'mensaje_enviado', (int) $registro['id'], null,
            "Correo enviado a {$registro['correo_cliente']} sobre el contenedor #{$registro['num_contenedor']}.");
    } else {
        registrarEvento($pdo, 'mensaje_fallido', (int) $registro['id'], null,
            "Falló el envío de correo a {$registro['correo_cliente']}: " . ($correo['error'] ?? 'error desconocido'));
    }

    echo json_encode([
        'success' => true,
        'message' => 'Operación marcada como completada.',
        'correo_enviado' => $correo['success'],
        'correo_error' => $correo['success'] ? null : ($correo['error'] ?? 'Fallo al enviar el correo.'),
    ]);
} catch (\PDOException $e) {
    error_log('completar_registro.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error del servidor. Intenta de nuevo más tarde.']);
}
?>
