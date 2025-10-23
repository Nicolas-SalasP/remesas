<?php
namespace App\Controllers;

use App\Services\TransactionService;
use App\Services\PricingService;
use App\Services\DashboardService;
use App\Services\UserService;
use App\Services\FileHandlerService; 
use Exception;

class AdminController extends BaseController
{
    private TransactionService $txService;
    private PricingService $pricingService;
    private UserService $userService;
    private DashboardService $dashboardService;
    private FileHandlerService $fileHandler; 

    public function __construct(
        TransactionService $txService,
        PricingService $pricingService,
        UserService $userService,
        DashboardService $dashboardService,
        FileHandlerService $fileHandler 
    ) {
        $this->txService = $txService;
        $this->pricingService = $pricingService;
        $this->userService = $userService;
        $this->dashboardService = $dashboardService;
        $this->fileHandler = $fileHandler; 

        $this->ensureAdmin();
    }

    public function updateRate(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->pricingService->adminUpdateRate(
            $adminId,
            (int)($data['tasaId'] ?? 0),
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
            (int)($data['paisId'] ?? 0),
            $data['newRole'] ?? ''
        );
        $this->sendJsonResponse(['success' => true]);
    }

    public function togglePaisStatus(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $newStatus = (bool)($data['newStatus'] ?? false);
        $this->pricingService->adminToggleCountryStatus($adminId, (int)($data['paisId'] ?? 0), $newStatus);
        $this->sendJsonResponse(['success' => true]);
    }

    public function updateVerificationStatus(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->userService->updateVerificationStatus(
            $adminId,
            (int)($data['userId'] ?? 0),
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
            (int)($data['userId'] ?? 0),
            $data['newStatus'] ?? ''
        );
        $this->sendJsonResponse(['success' => true]);
    }

    public function processTransaction(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->txService->adminConfirmPayment($adminId, (int)($data['transactionId'] ?? 0));
        $this->sendJsonResponse(['success' => true]);
    }

    public function rejectTransaction(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->txService->adminRejectPayment($adminId, (int)($data['transactionId'] ?? 0));
        $this->sendJsonResponse(['success' => true]);
    }

    public function adminUploadProof(): void
    {
        $adminId = $this->ensureLoggedIn();
        $transactionId = (int)($_POST['transactionId'] ?? 0);

        if ($transactionId <= 0 || !isset($_FILES['receiptFile'])) {
            throw new Exception('ID de transacción inválido o archivo no subido.', 400);
        }

        try {
             $directory = 'proof_of_sending';
             $filenamePrefix = 'tx_envio_' . $transactionId;
             $dbPath = $this->fileHandler->handleGenericUpload($_FILES['receiptFile'], $directory, $filenamePrefix); // Usar método genérico
             $this->txService->adminUploadProof($adminId, $transactionId, $dbPath);
             $this->sendJsonResponse(['success' => true]);
         } catch (Exception $e) {
             $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
         }
    }

    public function getDashboardStats(): void
    {
        $stats = $this->dashboardService->getAdminDashboardStats();
        $this->sendJsonResponse(['success' => true, 'stats' => $stats]);
    }
}