<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Exception;
use Twilio\Rest\Client as TwilioClient;

class NotificationService
{
    private LogService $logService;
    private const PROVEEDOR_WHATSAPP_NUMBER = '+56912345678';
    private const ADMIN_EMAIL_ADDRESS = 'nicolas.salas.1200@gmail.com';

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function logAdminAction(?int $userId, string $action, string $details): void
    {
        $this->logService->logAction($userId, $action, $details);
    }

    // --- MÉTODOS DE NOTIFICACIÓN WHATSAPP ---

    public function sendOrderToClientWhatsApp(array $txData, string $pdfUrl): bool
    {
        if (!defined('TWILIO_ACCOUNT_SID') || !defined('TWILIO_AUTH_TOKEN') || !defined('TWILIO_WHATSAPP_NUMBER') || empty(TWILIO_ACCOUNT_SID)) {
            return false;
        }

        if (empty($txData['TelefonoCliente'])) {
            return false;
        }

        $clientPhoneNumber = $txData['TelefonoCliente'];
        if (strpos($clientPhoneNumber, '+') !== 0) {
            $clientPhoneNumber = '+' . $clientPhoneNumber;
        }
        $formattedClientNumber = 'whatsapp:' . $clientPhoneNumber;

        $mensaje = "¡Hola {$txData['PrimerNombre']}! 👋\n\nTu orden de envío *#{$txData['TransaccionID']}* ha sido registrada con éxito en JCenvios.cl.\n\nAdjuntamos el detalle de tu orden en PDF.\n\nPor favor, realiza el pago según las instrucciones y sube tu comprobante en la sección 'Mi Historial' de nuestra web.\n\n¡Gracias por tu confianza!";

        try {
            $twilio = new TwilioClient(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
            $message = $twilio->messages->create(
                $formattedClientNumber,
                [
                    'from' => TWILIO_WHATSAPP_NUMBER,
                    'body' => $mensaje,
                    'mediaUrl' => [$pdfUrl]
                ]
            );
            $this->logService->logAction($txData['UserID'], 'Notificación WhatsApp Enviada', "Orden #{$txData['TransaccionID']} enviada. SID: " . $message->sid);
            return true;
        } catch (Exception $e) {
            error_log("Error Twilio: " . $e->getMessage());
            $this->logService->logAction($txData['UserID'], 'Error Notificación WhatsApp', "Fallo al enviar orden #{$txData['TransaccionID']}. Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendOrderToProviderWhatsApp(array $txData, string $pdfContent): bool
    {
        $mensaje = "NUEVA ORDEN URGENTE #{$txData['TransaccionID']} PENDIENTE DE PAGO AL BENEFICIARIO. Monto: {$txData['MontoDestino']} {$txData['MonedaDestino']}.";
        error_log("WHATSAPP - PROVEEDOR: Orden #{$txData['TransaccionID']} enviada para pago.");
        return true;
    }

    public function sendPaymentConfirmationToClientWhatsApp(array $txData): bool
    {
        if (!defined('TWILIO_ACCOUNT_SID') || empty(TWILIO_ACCOUNT_SID)) return false;

        if (empty($txData['TelefonoCliente'])) {
            $this->logService->logAction($txData['UserID'], 'Error Notificación WhatsApp', "Confirmación Pago: Teléfono no encontrado. TX ID: {$txData['TransaccionID']}");
            return false;
        }

        $clientPhoneNumber = $txData['TelefonoCliente'];
        if (strpos($clientPhoneNumber, '+') !== 0) {
            $clientPhoneNumber = '+' . $clientPhoneNumber;
        }
        $formattedClientNumber = 'whatsapp:' . $clientPhoneNumber;

        $mensaje = "¡Buenas noticias {$txData['PrimerNombre']}! 🎉\n\nTu remesa *#{$txData['TransaccionID']}* ha sido **PAGADA**.\n\nPuedes ver el comprobante de envío directamente en tu historial de transacciones en JCenvios.cl.\n\n¡Gracias por preferirnos!";

        try {
            $twilio = new TwilioClient(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
            $message = $twilio->messages->create(
                $formattedClientNumber,
                [
                    'from' => TWILIO_WHATSAPP_NUMBER,
                    'body' => $mensaje,
                ]
            );
            $this->logService->logAction($txData['UserID'], 'WhatsApp Confirmación Pago', "Orden #{$txData['TransaccionID']} notificada.");
            return true;
        } catch (Exception $e) {
            error_log("Error Twilio Pago: " . $e->getMessage());
            return false;
        }
    }

    // --- MÉTODOS DE EMAIL ---

    public function sendWelcomeEmail(string $email, string $nombre): bool
    {
        $mail = new PHPMailer(true);
        try {
            $this->configureSMTP($mail);
            $mail->addAddress($email, $nombre);
            $mail->Subject = "¡Bienvenido a JC Envíos!";

            $videoTutorialLink = "https://www.youtube.com/watch?v=dQw4w9WgXcQ";

            $mail->Body = "
            <html>
            <body>
                <p>Hola " . htmlspecialchars($nombre) . ",</p>
                <p>¡Te damos la bienvenida a <strong>JC Envíos</strong>! Estamos felices de tenerte con nosotros.</p>
                <p>Para ayudarte a comenzar, hemos preparado un breve video tutorial:</p>
                <p><a href='" . $videoTutorialLink . "'>Ver Video Tutorial</a></p>
                <p>Recuerda verificar tu identidad para comenzar a operar.</p>
                <p>Gracias por tu confianza,<br>El equipo de JC Envíos</p>
            </body>
            </html>";

            $mail->send();
            $this->logService->logAction(null, 'Email Bienvenida Enviado', "Enviado a: {$email}");
            return true;
        } catch (Exception $e) {
            error_log("Error envío email bienvenida: " . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordResetEmail(string $email, string $token): bool
    {
        $mail = new PHPMailer(true);
        try {
            $this->configureSMTP($mail);
            $mail->addAddress($email);
            $mail->Subject = "Restablece tu Contraseña en JC Envíos";

            $resetLink = BASE_URL . "/reset-password.php?token=" . urlencode($token);
            $mail->Body = "
            <html>
            <body>
                <p>Hola,</p>
                <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
                <p><a href=\"{$resetLink}\">Restablecer Contraseña</a></p>
                <p>Si no solicitaste esto, ignora este correo.</p>
                <p>Saludos,<br>El equipo de JC Envíos</p>
            </body>
            </html>";

            $mail->send();
            $this->logService->logAction(null, 'Email Recuperación Enviado', "Enviado a: {$email}");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function send2FABackupCodes(string $email, string $secretKey, array $backupCodes): bool
    {
        $mail = new PHPMailer(true);
        try {
            $this->configureSMTP($mail);
            $mail->addAddress($email);
            $mail->Subject = "Tus Códigos de Respaldo 2FA - JC Envíos";

            $codesList = "<ul>";
            foreach ($backupCodes as $code) {
                $codesList .= "<li>" . htmlspecialchars($code) . "</li>";
            }
            $codesList .= "</ul>";

            $mail->Body = "
            <html>
            <body>
                <p>Hola,</p>
                <p>Has activado 2FA. Guarda estos códigos de respaldo:</p>
                {$codesList}
                <p>Clave Secreta: " . htmlspecialchars($secretKey) . "</p>
            </body>
            </html>";

            $mail->send();
            $this->logService->logAction(null, 'Email Códigos 2FA Enviado', "Enviado a: {$email}");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function sendContactFormEmail(string $name, string $fromEmail, string $subject, string $message): bool
    {
        $mail = new PHPMailer(true);
        try {
            $this->configureSMTP($mail);
            $mail->addAddress(self::ADMIN_EMAIL_ADDRESS);
            $mail->addReplyTo($fromEmail, $name);
            $mail->Subject = "Contacto: " . $subject;
            $mail->Body = "
            <html>
            <body>
                <p><strong>Mensaje de:</strong> $name ($fromEmail)</p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
            </body>
            </html>";
            
            $mail->send();
            $this->logService->logAction(null, 'Formulario Contacto Enviado', "Enviado por: {$fromEmail}");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function sendNewOrderEmail(array $txData, string $pdfContent): bool
    {
        $mail = new PHPMailer(true);
        try {
            $this->configureSMTP($mail);
            $mail->addAddress($txData['Email'], $txData['PrimerNombre']);
            $mail->Subject = "Orden de Envío #" . $txData['TransaccionID'];
            
            $mail->Body = "
            <html>
            <body>
                <p>Hola {$txData['PrimerNombre']},</p>
                <p>Tu orden <strong>#{$txData['TransaccionID']}</strong> ha sido creada.</p>
                <p>Adjuntamos el comprobante. Por favor realiza el pago y sube el comprobante en la web.</p>
            </body>
            </html>";
            
            $mail->addStringAttachment($pdfContent, 'orden-'.$txData['TransaccionID'].'.pdf', 'base64', 'application/pdf');
            $mail->send();
            $this->logService->logAction($txData['UserID'], 'Email Orden Creada', "Enviado a: " . $txData['Email']);
            return true;
        } catch (Exception $e) {
            error_log("Error enviando email orden: " . $e->getMessage());
            return false;
        }
    }

    // --- MÉTODOS OPTIMIZADOS DE RECHAZO Y CORRECCIÓN ---

    public function sendCorrectionRequestEmail(string $email, string $nombre, int $txId, string $motivo): bool
    {
        $mail = new PHPMailer(true);
        try {
            $this->configureSMTP($mail);
            $mail->addAddress($email, $nombre);
        
            $mail->Subject = "Informacion sobre tu Orden #{$txId}";

            $mail->Body = "
            <html>
            <head>
                <style>
                    .alert-box { background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba; margin: 20px 0; }
                    .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                </style>
            </head>
            <body>
                <p>Hola " . htmlspecialchars($nombre) . ",</p>
                <p>Hemos revisado tu comprobante de pago para la orden <strong>#{$txId}</strong> y detectamos un problema:</p>
                
                <div style='background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba; margin: 15px 0;'>
                    <strong>Motivo:</strong> " . htmlspecialchars($motivo) . "
                </div>

                <p><strong>Tienes 48 horas para realizar esta correccion</strong>, de lo contrario la orden sera cancelada automáticamente.</p>
                
                <p>
                    <a href='" . BASE_URL . "/dashboard/historial.php' style='background-color: #007bff; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Subir nuevo comprobante</a>
                </p>
                
                <br>
                <p>Saludos,<br>El equipo de JC Envios</p>
            </body>
            </html>";

            $mail->AltBody = "Hola $nombre. Hay un problema con tu orden #$txId. Motivo: $motivo. Por favor sube el comprobante correcto en tu historial en las próximas 48 horas.";

            $mail->send();
            $this->logService->logAction(null, 'Email Corrección Enviado', "Enviado a: {$email} (TX: {$txId})");
            return true;
        } catch (Exception $e) {
            error_log("Error enviando email corrección: " . $e->getMessage());
            return false;
        }
    }

    public function sendCancellationEmail(string $email, string $nombre, int $txId, string $motivo): bool
    {
        $mail = new PHPMailer(true);
        try {
            $this->configureSMTP($mail);
            $mail->addAddress($email, $nombre);
            $mail->Subject = "Cancelación de Orden #{$txId}";

            $mail->Body = "
            <html>
            <body>
                <p>Hola " . htmlspecialchars($nombre) . ",</p>
                <p>Te informamos que tu orden <strong>#{$txId}</strong> ha sido <strong>CANCELADA</strong>.</p>
                
                <div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; margin: 15px 0;'>
                    <strong>Motivo:</strong> " . htmlspecialchars($motivo) . "
                </div>

                <p>Si crees que es un error, contáctanos respondiendo este correo.</p>
                <br>
                <p>Saludos,<br>El equipo de JC Envíos</p>
            </body>
            </html>";

            $mail->AltBody = "Hola $nombre. Tu orden #$txId ha sido cancelada. Motivo: $motivo.";

            $mail->send();
            $this->logService->logAction(null, 'Email Cancelación Enviado', "Enviado a: {$email} (TX: {$txId})");
            return true;
        } catch (Exception $e) {
            error_log("Error enviando email cancelación: " . $e->getMessage());
            return false;
        }
    }

    private function configureSMTP(PHPMailer $mail): void
    {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64'; 
        
        $mail->setFrom('no-responder@jcenvios.cl', 'JC Envíos');
        $mail->isHTML(true);
    }
}