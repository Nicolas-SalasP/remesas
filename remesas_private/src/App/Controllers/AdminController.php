<?php

namespace App\Controllers;

use App\Services\TransactionService;
use App\Services\PricingService;
use App\Services\UserService;
use Exception;

class AdminController extends BaseController
{
    private TransactionService $txService;
    private PricingService $pricingService;
    private UserService $userService;
    
    public function __construct(
        TransactionService $txService,
        PricingService $pricingService,
        UserService $userService
    ) {
        $this->txService = $txService;
        $this->pricingService = $pricingService;
        $this->userService = $userService;
        
        $this->ensureAdmin();
    }
    

    // GESTIÓN DE TASAS Y PAÍSES 

    public function updateRate(): void
    {
        $adminId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput();
            
            $this->pricingService->adminUpdateRate(
                $adminId,
                $data['tasaId'] ?? 0,
                (float)($data['nuevoValor'] ?? 0)
            );
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
    
    public function addPais(): void
    {
        $adminId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput();
            $this->pricingService->adminAddCountry(
                $adminId,
                $data['nombrePais'] ?? '',
                $data['codigoMoneda'] ?? '',
                $data['rol'] ?? ''
            );
            $this->sendJsonResponse(['success' => true], 201);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function updatePaisRol(): void
    {
        $adminId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput();
            $this->pricingService->adminUpdateCountryRole(
                $adminId,
                $data['paisId'] ?? 0,
                $data['newRole'] ?? ''
            );
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function togglePaisStatus(): void
    {
        $adminId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput();
            $newStatus = (bool)($data['newStatus'] ?? false);
            $this->pricingService->adminToggleCountryStatus($adminId, $data['paisId'] ?? 0, $newStatus);
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }

    // GESTIÓN DE USUARIOS 

    public function updateVerificationStatus(): void
    {
        $adminId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput();
            $this->userService->updateVerificationStatus(
                $adminId,
                $data['userId'] ?? 0,
                $data['newStatus'] ?? ''
            );
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function toggleUserBlock(): void
    {
        $adminId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput();
            $this->userService->toggleUserBlock(
                $adminId,
                $data['userId'] ?? 0,
                $data['newStatus'] ?? ''
            );
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // FLUJO DE TRANSACCIONES 

    public function processTransaction(): void
    {
        $adminId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput();
            $this->txService->adminConfirmPayment($adminId, $data['transactionId'] ?? 0);
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 409);
        }
    }

    public function rejectTransaction(): void
    {
        $adminId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput();
            $this->txService->adminRejectPayment($adminId, $data['transactionId'] ?? 0);
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 409);
        }
    }

    public function adminUploadProof(): void
    {
        $this->ensureAdmin();
        $adminId = $this->ensureLoggedIn();
        $transactionId = $_POST['transactionId'] ?? 0;
        
        if (empty($transactionId) || !isset($_FILES['receiptFile'])) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Datos incompletos o archivo no subido.'], 400);
        }
        
        $dbPath = 'uploads/proof_of_sending/tx_envio_' . $transactionId . '_' . uniqid() . '.jpg';
        
        try {
            $this->txService->adminUploadProof($adminId, $transactionId, $dbPath);
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}