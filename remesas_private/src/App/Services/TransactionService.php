<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Repositories\BeneficiaryRepository;
use App\Repositories\RateRepository;
use App\Repositories\UserRepository;
use Exception;
use App\Services\FileHandlerService;

class TransactionService
{
    private TransactionRepository $txRepository;
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private PDFService $pdfService;
    private FileHandlerService $fileHandler;
    
    public function __construct(
        TransactionRepository $txRepository,
        UserRepository $userRepository,
        NotificationService $notificationService,
        PDFService $pdfService,
        FileHandlerService $fileHandler 
    ) {
        $this->txRepository = $txRepository;
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
        $this->pdfService = $pdfService;
        $this->fileHandler = $fileHandler; 
    }

    // LÓGICA DEL FLUJO TRANSACCIONAL DEL CLIENTE

    public function createTransaction(array $data): int
    {
        $client = $this->userRepository->findUserById($data['userID']);

        if (!$client) {
            throw new Exception("Usuario no encontrado.", 404);
        }
        if ($client['VerificacionEstado'] !== 'Verificado') {
            throw new Exception("Tu cuenta debe estar verificada para realizar transacciones.", 403);
        }
        if (empty($client['Telefono'])) {
             error_log("Intento de transacción sin teléfono registrado para UserID: {$data['userID']}");
            throw new Exception("Falta tu número de teléfono en el perfil. Actualízalo para poder realizar transacciones.", 400);
        }
        $requiredFields = ['userID', 'cuentaID', 'tasaID', 'montoOrigen', 'monedaOrigen', 'montoDestino', 'monedaDestino', 'formaDePago'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                 throw new Exception("Faltan datos para crear la transacción. Campo requerido: $field", 400);
            }
        }
        
        try {
            $transactionId = $this->txRepository->create($data);
    
            $txData = $this->txRepository->getFullTransactionDetails($transactionId);
            if (!$txData) {
                 throw new Exception("No se pudieron obtener los detalles completos de la transacción recién creada.", 500);
            }
    
            $txData['TelefonoCliente'] = $client['Telefono'];
            $txData['PrimerNombre'] = $client['PrimerNombre'];
    
            $pdfContent = $this->pdfService->generateOrder($txData);
            $pdfUrl = $this->fileHandler->savePdfTemporarily($pdfContent, $transactionId);
    
            $whatsappSent = $this->notificationService->sendOrderToClientWhatsApp($txData, $pdfUrl);
            
            if (!$whatsappSent) {
                 $this->notificationService->logAdminAction($data['userID'], 'Advertencia Notificación', "TX ID $transactionId: No se pudo enviar WhatsApp al cliente.");
            }
            
            $this->notificationService->logAdminAction($data['userID'], 'Creación de Transacción', "TX ID: $transactionId - Notificación WhatsApp: " . ($whatsappSent ? 'Éxito' : 'Fallo'));
            return $transactionId;

        } catch (Exception $e) {
             error_log("Error al crear transacción o notificar: " . $e->getMessage());
            throw $e; 
        }
    }

    public function uploadUserReceipt(int $txId, int $userId, string $path): bool
    {
        if (empty($path)) {
             throw new Exception("La ruta del archivo es inválida.", 500);
        }
        $affectedRows = $this->txRepository->uploadUserReceipt($txId, $userId, $path);

        if ($affectedRows === 0) {
            $txExists = $this->txRepository->getFullTransactionDetails($txId);
            if (!$txExists || $txExists['UserID'] != $userId) {
                 throw new Exception("La transacción no existe o no te pertenece.", 404);
            } elseif ($txExists['Estado'] !== 'Pendiente de Pago') {
                 throw new Exception("No se puede subir comprobante. El estado actual es '{$txExists['Estado']}'.", 409);
            } else {
                 throw new Exception("No se pudo actualizar la transacción. Inténtalo de nuevo.", 500);
            }
        }

        $this->notificationService->logAdminAction($userId, 'Subida de Comprobante', "TX ID: $txId");
        return true;
    }

    public function cancelTransaction(int $txId, int $userId): bool
    {
        $affectedRows = $this->txRepository->cancel($txId, $userId);

        if ($affectedRows === 0) {
             $txExists = $this->txRepository->getFullTransactionDetails($txId);
            if (!$txExists || $txExists['UserID'] != $userId) {
                 throw new Exception("La transacción no existe o no te pertenece.", 404);
            } elseif ($txExists['Estado'] !== 'Pendiente de Pago') {
                throw new Exception("No se puede cancelar. El estado actual es '{$txExists['Estado']}'.", 409);
            } else {
                 throw new Exception("No se pudo cancelar la transacción. Inténtalo de nuevo.", 500);
            }
        }

        $this->notificationService->logAdminAction($userId, 'Usuario canceló transacción', "TX ID: $txId");
        return true;
    }

    // LÓGICA DEL FLUJO DE ADMINISTRACIÓN

    public function adminConfirmPayment(int $adminId, int $txId): bool
    {
        $affectedRows = $this->txRepository->updateStatus($txId, 'En Proceso', 'En Verificación');

        if ($affectedRows === 0) {
            $txExists = $this->txRepository->getFullTransactionDetails($txId);
             if (!$txExists) {
                 throw new Exception("La transacción no existe.", 404);
            } elseif ($txExists['Estado'] !== 'En Verificación') {
                throw new Exception("El estado de la transacción es '{$txExists['Estado']}', no 'En Verificación'.", 409);
            } else {
                 throw new Exception("No se pudo confirmar el pago. Inténtalo de nuevo.", 500);
            }
        }

        $this->notificationService->logAdminAction($adminId, 'Admin confirmó pago', "TX ID: $txId. Estado cambiado a 'En Proceso'.");
        return true;
    }

    public function adminRejectPayment(int $adminId, int $txId): bool
    {
        $affectedRows = $this->txRepository->updateStatus($txId, 'Cancelado', 'En Verificación');

        if ($affectedRows === 0) {
            $txExists = $this->txRepository->getFullTransactionDetails($txId);
             if (!$txExists) {
                 throw new Exception("La transacción no existe.", 404);
            } elseif ($txExists['Estado'] !== 'En Verificación') {
                throw new Exception("El estado de la transacción es '{$txExists['Estado']}', no 'En Verificación'.", 409);
            } else {
                 throw new Exception("No se pudo rechazar el pago. Inténtalo de nuevo.", 500);
            }
        }

        $this->notificationService->logAdminAction($adminId, 'Admin rechazó pago', "TX ID: $txId. Estado cambiado a 'Cancelado'.");
        return true;
    }

    public function adminUploadProof(int $adminId, int $txId, string $proofPath): bool
    {
         if (empty($proofPath)) {
             throw new Exception("La ruta del comprobante de envío es inválida.", 500);
        }
        
        $affectedRows = $this->txRepository->uploadAdminProof($txId, $proofPath);

        if ($affectedRows === 0) {
             $txExists = $this->txRepository->getFullTransactionDetails($txId);
             if (!$txExists) {
                 throw new Exception("La transacción no existe.", 404);
            } elseif ($txExists['Estado'] !== 'En Proceso') {
                throw new Exception("El estado de la transacción es '{$txExists['Estado']}', no 'En Proceso'.", 409);
            } else {
                 throw new Exception("No se pudo subir el comprobante de envío. Inténtalo de nuevo.", 500);
            }
        }

        $txData = $this->txRepository->getFullTransactionDetails($txId);
        if ($txData && !empty($txData['TelefonoCliente'])) {
            $this->notificationService->sendPaymentConfirmationToClientWhatsApp($txData);
        } else {
             $this->notificationService->logAdminAction($adminId, 'Advertencia Notificación', "TX ID $txId: No se pudo notificar al cliente sobre el pago (faltan datos).");
        }

        $this->notificationService->logAdminAction($adminId, 'Admin completó transacción', "TX ID: $txId. Comprobante de envío subido. Estado: 'Pagado'.");
        return true;
    }
}