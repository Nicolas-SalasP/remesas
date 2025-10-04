<?php

namespace App\Controllers;

use App\Services\TransactionService;
use App\Services\PricingService;
use App\Services\DashboardService;
use App\Services\UserService;
use Exception;

class AdminController extends BaseController
{
    private TransactionService $txService;
    private PricingService $pricingService;
    private UserService $userService;
    private DashboardService $dashboardService;
    
    public function __construct(
        TransactionService $txService,
        PricingService $pricingService,
        UserService $userService,
        DashboardService $dashboardService
    ) {
        $this->txService = $txService;
        $this->pricingService = $pricingService;
        $this->userService = $userService;
        $this->dashboardService = $dashboardService;
        
        $this->ensureAdmin();
    }
    
    // GESTIÓN DE TASAS Y PAÍSES 

    public function updateRate(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        
        $this->pricingService->adminUpdateRate(
            $adminId,
            $data['tasaId'] ?? 0,
            (float)($data['nuevoValor'] ?? 0)
        );
        $this->sendJsonResponse(['success' => true]);
    }
    
    public function addPais(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->pricingService->adminAddCountry(
            $adminId,
            $data['nombrePais'] ?? '',
            $data['codigoMoneda'] ?? '',
            $data['rol'] ?? ''
        );
        $this->sendJsonResponse(['success' => true], 201);
    }

    public function updatePaisRol(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->pricingService->adminUpdateCountryRole(
            $adminId,
            $data['paisId'] ?? 0,
            $data['newRole'] ?? ''
        );
        $this->sendJsonResponse(['success' => true]);
    }

    public function togglePaisStatus(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $newStatus = (bool)($data['newStatus'] ?? false);
        $this->pricingService->adminToggleCountryStatus($adminId, $data['paisId'] ?? 0, $newStatus);
        $this->sendJsonResponse(['success' => true]);
    }

    // GESTIÓN DE USUARIOS 

    public function updateVerificationStatus(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->userService->updateVerificationStatus(
            $adminId,
            $data['userId'] ?? 0,
            $data['newStatus'] ?? ''
        );
        $this->sendJsonResponse(['success' => true]);
    }

    public function toggleUserBlock(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->userService->toggleUserBlock(
            $adminId,
            $data['userId'] ?? 0,
            $data['newStatus'] ?? ''
        );
        $this->sendJsonResponse(['success' => true]);
    }

    // FLUJO DE TRANSACCIONES 

    public function processTransaction(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->txService->adminConfirmPayment($adminId, $data['transactionId'] ?? 0);
        $this->sendJsonResponse(['success' => true]);
    }

    public function rejectTransaction(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->txService->adminRejectPayment($adminId, $data['transactionId'] ?? 0);
        $this->sendJsonResponse(['success' => true]);
    }

    public function adminUploadProof(): void
    {
        $this->ensureAdmin();
        $adminId = $this->ensureLoggedIn();
        $transactionId = $_POST['transactionId'] ?? 0;
        
        if (empty($transactionId) || !isset($_FILES['receiptFile'])) {
            throw new Exception('Datos incompletos o archivo no subido.', 400);
        }
        
        $dbPath = 'uploads/proof_of_sending/tx_envio_' . $transactionId . '_' . uniqid() . '.jpg';
        
        $this->txService->adminUploadProof($adminId, $transactionId, $dbPath);
        $this->sendJsonResponse(['success' => true]);
    }

    public function getDashboardStats(): void
    {
        try {
            $stats = $this->dashboardService->getAdminDashboardStats();
            $this->sendJsonResponse(['success' => true, 'stats' => $stats]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}