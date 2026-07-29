<?php
/**
 * Registra un evento en la bitácora (tabla eventos_log) para que el
 * Administrador pueda ver todos los movimientos del sistema:
 * creación de QR, lecturas de QR por el guardia, operaciones completadas
 * y mensajes de WhatsApp enviados o fallidos.
 *
 * @param PDO         $pdo
 * @param string      $tipo        creacion_qr | lectura_qr | operacion_completada | mensaje_enviado | mensaje_fallido
 * @param int|null    $registroId  ID de registros_accesos relacionado, si aplica
 * @param int|null    $usuarioId   ID del usuario que originó el evento, si se conoce
 * @param string      $detalle     Descripción legible del evento
 */
function registrarEvento(PDO $pdo, string $tipo, ?int $registroId, ?int $usuarioId, string $detalle): void {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO eventos_log (tipo_evento, registro_id, usuario_id, detalle) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$tipo, $registroId, $usuarioId, $detalle]);
    } catch (\PDOException $e) {
        // No interrumpimos el flujo principal si falla el registro de la bitácora,
        // pero lo dejamos en el log de errores del servidor para depuración.
        error_log('No se pudo registrar evento en bitácora: ' . $e->getMessage());
    }
}
?>
