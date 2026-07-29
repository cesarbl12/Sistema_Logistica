<?php
/**
 * Envío de correo transaccional vía SMTP (Gmail) usando PHPMailer.
 *
 * Credenciales: cuenta de Gmail con una "contraseña de aplicación" generada en
 * https://myaccount.google.com/apppasswords (requiere verificación en 2 pasos
 * activada). No es la contraseña normal de la cuenta.
 */

require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'cesarblgbt@gmail.com');
define('SMTP_PASS', 'dudauekbsgiwkgvr');
define('SMTP_FROM_NAME', 'Portal de Accesos y Envíos');

/**
 * Envía un correo usando la cuenta SMTP configurada arriba.
 *
 * @param string $destinatario Correo del cliente.
 * @param string $asunto       Asunto del correo.
 * @param string $cuerpoHtml   Cuerpo del mensaje (HTML).
 * @return array{success: bool, error?: string}
 */
function enviarCorreo(string $destinatario, string $asunto, string $cuerpoHtml): array {
    if (SMTP_USER === '' || SMTP_PASS === '') {
        return ['success' => false, 'error' => 'Credenciales SMTP no configuradas en config/email.php'];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->AltBody = strip_tags($cuerpoHtml);

        $mail->send();

        return ['success' => true];
    } catch (PHPMailerException $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}
?>
