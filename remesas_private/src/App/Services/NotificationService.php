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
    private const WHATSAPP_API_URL = 'https://api.whatsapp-provider.com/send';
    private const ADMIN_EMAIL_ADDRESS = 'nicolas.salas.1200@gmail.com';

    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
    }

    public function logAdminAction(?int $userId, string $action, string $details): void
    {
        $this->logService->logAction($userId, $action, $details);
    }

    // MÉTODOS DE NOTIFICACIÓN WHATSAPP 

    public function sendOrderToClientWhatsApp(array $txData, string $pdfUrl): bool
    {
        if (!defined('TWILIO_ACCOUNT_SID') || !defined('TWILIO_AUTH_TOKEN') || !defined('TWILIO_WHATSAPP_NUMBER') || empty(TWILIO_ACCOUNT_SID)) {
            error_log("Error crítico: Credenciales de Twilio no configuradas en config.php");
            $this->logService->logAction($txData['UserID'] ?? null, 'Error Notificación WhatsApp', "Credenciales Twilio no configuradas. TX ID: {$txData['TransaccionID']}");
            return false;
        }
        if (empty($txData['TelefonoCliente'])) {
            error_log("Error Notificación WhatsApp: No se encontró teléfono para el UserID: {$txData['UserID']}. TX ID: {$txData['TransaccionID']}");
            $this->logService->logAction($txData['UserID'], 'Error Notificación WhatsApp', "Teléfono de cliente no encontrado. TX ID: {$txData['TransaccionID']}");
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
            error_log("Error de Twilio al enviar WhatsApp a {$formattedClientNumber} para TX ID {$txData['TransaccionID']}: " . $e->getMessage());
            $this->logService->logAction($txData['UserID'], 'Error Notificación WhatsApp', "Fallo al enviar orden #{$txData['TransaccionID']}. Error Twilio: " . $e->getMessage());
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
        if (empty($txData['TelefonoCliente'])) {
            error_log("Error Notificación WhatsApp: No se encontró teléfono para el UserID: {$txData['UserID']}. TX ID: {$txData['TransaccionID']}");
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

            $this->logService->logAction($txData['UserID'], 'Notificación WhatsApp Confirmación Pago', "Orden #{$txData['TransaccionID']} notificada. SID: " . $message->sid);
            return true;

        } catch (Exception $e) {
            error_log("Error de Twilio al enviar confirmación de pago a {$formattedClientNumber} para TX ID {$txData['TransaccionID']}: " . $e->getMessage());
            $this->logService->logAction($txData['UserID'], 'Error Notificación WhatsApp', "Fallo al confirmar pago orden #{$txData['TransaccionID']}. Error Twilio: " . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordResetEmail(string $email, string $token): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->setFrom('no-responder@jcenvios.cl', 'JC Envíos - Recuperación');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Restablece tu Contraseña en JC Envíos";

            $resetLink = BASE_URL . "/reset-password.php?token=" . urlencode($token);
            $mail->Body = "Hola,<br><br>Recibimos una solicitud para restablecer tu contraseña en JCenvios.cl.<br><br>" .
                "Haz clic en el siguiente enlace para crear una nueva contraseña:<br>" .
                "<a href=\"{$resetLink}\">Restablecer Contraseña</a><br><br>" .
                "Si no solicitaste esto, puedes ignorar este correo.<br><br>" .
                "Saludos,<br>El equipo de JC Envíos";
            $mail->AltBody = "Hola,\n\nRecibimos una solicitud para restablecer tu contraseña en JCenvios.cl.\n\n" .
                "Copia y pega el siguiente enlace en tu navegador para crear una nueva contraseña:\n" .
                $resetLink . "\n\n" .
                "Si no solicitaste esto, puedes ignorar este correo.\n\n" .
                "Saludos,\nEl equipo de JC Envíos";


            $mail->send();
            $this->logService->logAction(null, 'Email Recuperación Enviado', "Enviado a: {$email}");
            return true;
        } catch (PHPMailerException $e) {
            error_log("PHPMailer Error: No se pudo enviar el email de recuperación a {$email}. Error: {$mail->ErrorInfo}");
            $this->logService->logAction(null, 'Error Email Recuperación', "Fallo al enviar a: {$email}. Error: {$mail->ErrorInfo}");
            return false;
        } catch (Exception $e) {
            error_log("Error General al enviar email de recuperación a {$email}: {$e->getMessage()}");
            $this->logService->logAction(null, 'Error Email Recuperación', "Fallo al enviar a: {$email}. Error General: {$e->getMessage()}");
            return false;
        }
    }

    public function sendContactFormEmail(string $name, string $fromEmail, string $subject, string $message): bool
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeFromEmail = htmlspecialchars($fromEmail, ENT_QUOTES, 'UTF-8');
        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $safeMessageHtml = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $safeMessageText = htmlspecialchars_decode($safeMessageHtml, ENT_QUOTES);


        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->setFrom('no-responder@jcenvios.cl', 'Formulario de Contacto (JC Envíos)');
            $mail->addAddress(self::ADMIN_EMAIL_ADDRESS);
            $mail->addReplyTo($safeFromEmail, $safeName);
            $mail->isHTML(true);
            $mail->Subject = "Nuevo Mensaje de Contacto: " . $safeSubject;
            $mail->CharSet = 'UTF-8';

            $mail->Body = "Has recibido un nuevo mensaje desde el formulario de contacto de JCenvios.cl:<br><br>" .
                "<strong>Nombre:</strong> {$safeName}<br>" .
                "<strong>Correo:</strong> {$safeFromEmail}<br>" .
                "<strong>Asunto:</strong> {$safeSubject}<br>" .
                "<strong>Mensaje:</strong><br><blockquote style='border-left: 2px solid #ccc; padding-left: 10px; margin-left: 5px;'>" .
                $safeMessageHtml .
                "</blockquote>";
            
            $mail->AltBody = "Has recibido un nuevo mensaje desde el formulario de contacto de JCenvios.cl:\n\n" .
                "Nombre: {$safeName}\n" .
                "Correo: {$safeFromEmail}\n" .
                "Asunto: {$safeSubject}\n" .
                "Mensaje:\n" .
                $safeMessageText;

            $mail->send();
            $this->logService->logAction(null, 'Formulario Contacto Enviado', "Enviado por: {$safeFromEmail}");
            return true;
        } catch (PHPMailerException $e) {
            error_log("PHPMailer Error: No se pudo enviar el email de contacto de {$fromEmail}. Error: {$mail->ErrorInfo}");
            $this->logService->logAction(null, 'Error Email Contacto', "Fallo al enviar de: {$fromEmail}. Error: {$mail->ErrorInfo}");
            return false;
        } catch (Exception $e) {
            error_log("Error General al enviar email de contacto de {$fromEmail}: {$e->getMessage()}");
            $this->logService->logAction(null, 'Error Email Contacto', "Fallo al enviar de: {$fromEmail}. Error General: {$e->getMessage()}");
            return false;
        }
    }
}