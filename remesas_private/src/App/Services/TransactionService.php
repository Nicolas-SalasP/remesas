<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Repositories\BeneficiaryRepository;
use App\Repositories\RateRepository;
use App\Repositories\UserRepository;
use Exception;

class TransactionService
{
    private TransactionRepository $txRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private PDFService $pdfService;

    public function __construct(
        TransactionRepository $txRepository,
        UserRepository $userRepository,
        NotificationService $notificationService,
        PDFService $pdfService
    ) {
        $this->txRepository = $txRepository;
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
        $this->pdfService = $pdfService;
    }

    // LÓGICA DEL FLUJO TRANSACCIONAL DEL CLIENTE

    public function createTransaction(array $data): int
    {
        $client = $this->userRepository->findUserById($data['userID']);
        
        if ($client['VerificacionEstado'] !== 'Verificado') {
            throw new Exception("Tu cuenta debe estar verificada para realizar transacciones.", 403); 
        }
        if (empty($client['Telefono'])) {
            throw new Exception("El número de teléfono del perfil es requerido para enviar notificaciones.", 400);
        }
        
        $transactionId = $this->txRepository->create($data);
        
        $txData = $this->txRepository->getFullTransactionDetails($transactionId);

        $txData['TelefonoCliente'] = $client['Telefono'];
        $txData['PrimerNombre'] = $client['PrimerNombre'];
        try {
            $pdfContent = $this->pdfService->generateOrder($txData); // Genera el PDF binario
            
            // A. Notificar al Cliente (WhatsApp)
            $this->notificationService->sendOrderToClientWhatsApp($txData, $pdfContent);
            
            // B. Notificar al Proveedor (WhatsApp)
            $this->notificationService->sendOrderToProviderWhatsApp($txData, $pdfContent);

        } catch (Exception $e) {
            // Si la automatización falla (ej. API de WhatsApp caída), marcamos el error en los logs.
            $this->notificationService->logAdminAction($data['userID'], 'Error Crítico de Automatización', "TX ID $transactionId: Fallo en envío de WhatsApp. Revisión Manual.");
            // Permitimos que la orden se cree para no frustrar al cliente.
        }
        
        $this->notificationService->logAdminAction($data['userID'], 'Creación de Transacción', "TX ID: $transactionId");
        return $transactionId;
    }

    public function uploadUserReceipt(int $txId, int $userId, string $path): bool
    {
        $affectedRows = $this->txRepository->uploadUserReceipt($txId, $userId, $path);
        
        if ($affectedRows === 0) {
            throw new Exception("No se pudo subir el comprobante. La orden no existe o ya está en proceso.", 409);
        }
        
        $this->notificationService->logAdminAction($userId, 'Subida de Comprobante', "TX ID: $txId");
        return true;
    }

    public function cancelTransaction(int $txId, int $userId): bool
    {
        $affectedRows = $this->txRepository->cancel($txId, $userId);

        if ($affectedRows === 0) {
            throw new Exception("No se pudo cancelar. La transacción ya fue procesada.", 409);
        }
        
        $this->notificationService->logAdminAction($userId, 'Usuario canceló transacción', "TX ID: $txId");
        return true;
    }

    // LÓGICA DEL FLUJO DE ADMINISTRACIÓN

    public function adminConfirmPayment(int $adminId, int $txId): bool
    {
        $affectedRows = $this->txRepository->updateStatus($txId, 'En Proceso', 'En Verificación');

        if ($affectedRows === 0) {
            throw new Exception("El estado de la transacción no es 'En Verificación'.", 409);
        }
        
        $this->notificationService->logAdminAction($adminId, 'Admin procesó transacción', "TX ID: $txId. Pago confirmado.");
        return true;
    }

    public function adminRejectPayment(int $adminId, int $txId): bool
    {
        $affectedRows = $this->txRepository->updateStatus($txId, 'Cancelado', 'En Verificación');

        if ($affectedRows === 0) {
            throw new Exception("El estado de la transacción no es 'En Verificación'.", 409);
        }
        
        $this->notificationService->logAdminAction($adminId, 'Admin rechazó pago de transacción', "TX ID: $txId. Monto no recibido.");
        return true;
    }

    public function adminUploadProof(int $adminId, int $txId, string $proofPath): bool
    {
        $affectedRows = $this->txRepository->uploadAdminProof($txId, $proofPath);

        if ($affectedRows === 0) {
            throw new Exception("El estado de la transacción debe ser 'En Proceso'.", 409);
        }
        
        $txData = $this->txRepository->getFullTransactionDetails($txId);

        $this->notificationService->sendPaymentConfirmationToClientWhatsApp($txData);

        $this->notificationService->logAdminAction($adminId, 'Admin subió comprobante de envío', "TX ID: $txId. Pago finalizado.");
        return true;
    }
}