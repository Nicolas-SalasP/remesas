<?php
namespace App\Controllers;

use App\Services\TransactionService;
use App\Services\PricingService;
use App\Services\DashboardService;
use App\Services\UserService;
use App\Repositories\RolRepository;
use App\Repositories\CuentasAdminRepository;
use Exception;

class AdminController extends BaseController
{
    private TransactionService $txService;
    private PricingService $pricingService;
    private UserService $userService;
    private DashboardService $dashboardService;
    private RolRepository $rolRepo;
    private CuentasAdminRepository $cuentasAdminRepo;

    public function __construct(
        TransactionService $txService,
        PricingService $pricingService,
        UserService $userService,
        DashboardService $dashboardService,
        RolRepository $rolRepo,
        CuentasAdminRepository $cuentasAdminRepo
    ) {
        $this->txService = $txService;
        $this->pricingService = $pricingService;
        $this->userService = $userService;
        $this->dashboardService = $dashboardService;
        $this->rolRepo = $rolRepo;
        $this->cuentasAdminRepo = $cuentasAdminRepo;
        $this->ensureAdmin();
    }

    public function rejectTransaction(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();

        $txId = (int) ($data['transactionId'] ?? 0);
        $reason = $data['reason'] ?? '';
        $actionType = $data['actionType'] ?? 'cancel';

        if ($txId <= 0) {
            $this->sendJsonResponse(['success' => false, 'error' => 'ID de transacción inválido.'], 400);
            return;
        }

        $this->txService->adminRejectPayment($adminId, $txId, $reason, $actionType === 'retry');
        $this->sendJsonResponse(['success' => true]);
    }

    public function upsertRate(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $resultData = $this->pricingService->adminUpsertRate($adminId, $data);
        $this->sendJsonResponse(['success' => true, 'newTasaId' => $resultData['TasaID']]);
    }

    public function addPais(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->pricingService->adminAddCountry($adminId, $data['nombrePais'] ?? '', $data['codigoMoneda'] ?? '', $data['rol'] ?? '');
        $this->sendJsonResponse(['success' => true], 201);
    }

    public function updatePais(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->pricingService->adminUpdateCountry($adminId, (int) ($data['paisId'] ?? 0), $data['nombrePais'] ?? '', $data['codigoMoneda'] ?? '');
        $this->sendJsonResponse(['success' => true]);
    }

    public function updatePaisRol(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->pricingService->adminUpdateCountryRole($adminId, (int) ($data['paisId'] ?? 0), $data['newRole'] ?? '');
        $this->sendJsonResponse(['success' => true]);
    }

    public function togglePaisStatus(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $newStatus = (bool) ($data['newStatus'] ?? false);
        $this->pricingService->adminToggleCountryStatus($adminId, (int) ($data['paisId'] ?? 0), $newStatus);
        $this->sendJsonResponse(['success' => true]);
    }

    public function processTransaction(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->txService->adminConfirmPayment($adminId, (int) ($data['transactionId'] ?? 0));
        $this->sendJsonResponse(['success' => true]);
    }

    public function adminUploadProof(): void
    {
        $adminId = $this->ensureLoggedIn();
        $transactionId = (int) ($_POST['transactionId'] ?? 0);
        $fileData = $_FILES['receiptFile'] ?? null;
        $comisionDestino = isset($_POST['comisionDestino']) ? (float) $_POST['comisionDestino'] : 0.00;

        if ($transactionId <= 0 || $fileData === null) {
            $this->sendJsonResponse(['success' => false, 'error' => 'ID de transacción inválido o archivo no recibido.'], 400);
            return;
        }

        try {
            $this->txService->handleAdminProofUpload($adminId, $transactionId, $fileData, $comisionDestino);
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() >= 400 ? $e->getCode() : 500);
        }
    }
    
    public function updateVerificationStatus(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $targetUserId = (int) ($data['userId'] ?? 0);
        $newStatusName = (string) ($data['newStatus'] ?? '');

        if ($targetUserId <= 0 || empty($newStatusName)) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Datos inválidos.'], 400);
            return;
        }
        $this->userService->updateVerificationStatus($adminId, $targetUserId, $newStatusName);
        $this->sendJsonResponse(['success' => true, 'message' => 'Estado actualizado.']);
    }

    public function toggleUserBlock(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        
        $targetUserId = (int)($data['userId'] ?? 0);
        $newStatus = (string)($data['newStatus'] ?? ''); 

        if ($targetUserId <= 0 || empty($newStatus)) {
             $this->sendJsonResponse(['success' => false, 'error' => 'Datos de usuario o estado inválidos.'], 400);
             return;
        }
        
        $this->userService->toggleUserBlock($adminId, $targetUserId, $newStatus);
        $this->sendJsonResponse(['success' => true, 'message' => 'Estado de bloqueo actualizado.']);
    }
    public function getDashboardStats(): void
    {
        $stats = $this->dashboardService->getAdminDashboardStats();
        $this->sendJsonResponse(['success' => true, 'stats' => $stats]);
    }

    public function updateUserRole(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $targetUserId = (int) ($data['userId'] ?? 0);
        $newRoleId = (int) ($data['newRoleId'] ?? 0);

        if ($targetUserId <= 0 || $newRoleId <= 0) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Datos inválidos.'], 400);
            return;
        }
        $this->userService->adminUpdateUserRole($adminId, $targetUserId, $newRoleId);
        $this->sendJsonResponse(['success' => true, 'message' => 'Rol actualizado.']);
    }

    public function deleteUser(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $targetUserId = (int) ($data['userId'] ?? 0);

        if ($targetUserId <= 0) {
            $this->sendJsonResponse(['success' => false, 'error' => 'ID de usuario inválido.'], 400);
            return;
        }
        $this->userService->adminDeleteUser($adminId, $targetUserId);
        $this->sendJsonResponse(['success' => true, 'message' => 'Usuario eliminado.']);
    }

    public function getCuentasAdmin(): void
    {
        $cuentas = $this->cuentasAdminRepo->findAll();
        $this->sendJsonResponse(['success' => true, 'cuentas' => $cuentas]);
    }

    public function saveCuentaAdmin(): void
    {
        $this->ensureLoggedIn();
        $data = $this->getJsonInput();

        if (empty($data['banco']) || empty($data['titular']) || empty($data['numeroCuenta']) || empty($data['rut'])) {
            throw new Exception("Faltan campos obligatorios.", 400);
        }

        if (isset($data['id']) && $data['id'] > 0) {
            $this->cuentasAdminRepo->update((int) $data['id'], $data);
        } else {
            $this->cuentasAdminRepo->create($data);
        }
        $this->sendJsonResponse(['success' => true]);
    }

    public function deleteCuentaAdmin(): void
    {
        $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->cuentasAdminRepo->delete((int) $data['id']);
        $this->sendJsonResponse(['success' => true]);
    }
}