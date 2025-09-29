<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Exception;

class NotificationService
{
    private LogService $logService;
    private const PROVEEDOR_WHATSAPP_NUMBER = '+56912345678'; 
    private const WHATSAPP_API_URL = 'https://api.whatsapp-provider.com/send'; 

    public function __construct(LogService $logService) 
    {
        $this->logService = $logService;
    }

    // LÓGICA DE AUDITORÍA 

    public function logAdminAction(?int $userId, string $action, string $details): void 
    {
        $this->logService->logAction($userId, $action, $details);
    }

    // MÉTODOS DE NOTIFICACIÓN WHATSAPP 

    public function sendOrderToClientWhatsApp(array $txData, string $pdfContent): bool
    {
        // En una implementación real, se enviaría la petición HTTP a la API de WhatsApp.
        
        $mensaje = "¡Hola {$txData['PrimerNombre']}! Tu orden #{$txData['TransaccionID']} ha sido registrada. Adjuntamos tu factura. Por favor, realiza el pago.";
        
        // Lógica de API (MOCK):
        // $response = HttpClient::post(self::WHATSAPP_API_URL, [ ... ]);
        
        error_log("WHATSAPP - CLIENTE: Orden #{$txData['TransaccionID']} enviada al número: {$txData['Telefono']}.");
        return true; 
    }

    public function sendOrderToProviderWhatsApp(array $txData, string $pdfContent): bool
    {
        // NOTA: Se le notifica al proveedor para que proceda con el pago al beneficiario.
        $mensaje = "NUEVA ORDEN URGENTE #{$txData['TransaccionID']} PENDIENTE DE PAGO AL BENEFICIARIO. Monto: {$txData['MontoDestino']} {$txData['MonedaDestino']}.";
        
        // Lógica de API (MOCK):
        // $response = HttpClient::post(self::WHATSAPP_API_URL, [ 'to' => self::PROVEEDOR_WHATSAPP_NUMBER, ... ]);
        
        error_log("WHATSAPP - PROVEEDOR: Orden #{$txData['TransaccionID']} enviada para pago.");
        return true;
    }

    public function sendPaymentConfirmationToClientWhatsApp(array $txData): bool
    {
        $mensaje = "¡Tu remesa #{$txData['TransaccionID']} ha sido PAGADA! El comprobante de envío ya está visible en tu historial.";
        
        error_log("WHATSAPP - CLIENTE: Notificación de PAGO COMPLETO para orden #{$txData['TransaccionID']}.");
        return true;
    }

    // MÉTODOS DE NOTIFICACIÓN EMAIL 

    public function sendPasswordResetEmail(string $email, string $token): bool
    { 
        $mail = new PHPMailer(true);
        try {
            $mail->setFrom('no-responder@jcenvios.cl', 'Restablecimiento de Contraseña');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Restablece tu Contraseña";
            
            $resetLink = BASE_URL . "/reset-password.php?token=" . $token; 
            $mail->Body = "Haz clic aquí para restablecer tu contraseña: <a href=\"{$resetLink}\">Restablecer Contraseña</a>.";
            
            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log("PHPMailer Error: No se pudo enviar el email a {$email}. Error: {$e->getMessage()}");
            return false;
        }
    }
}