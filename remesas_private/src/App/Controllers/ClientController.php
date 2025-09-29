<?php

namespace App\Controllers;

use App\Services\TransactionService;
use App\Services\PricingService;
use App\Services\BeneficiaryService;
use App\Services\UserService;
use Exception;

class ClientController extends BaseController
{
    private TransactionService $txService;
    private PricingService $pricingService;
    private BeneficiaryService $beneficiaryService;
    private UserService $userService;

    public function __construct(
        TransactionService $txService,
        PricingService $pricingService,
        BeneficiaryService $beneficiaryService,
        UserService $userService
    ) {
        $this->txService = $txService;
        $this->pricingService = $pricingService;
        $this->beneficiaryService = $beneficiaryService;
        $this->userService = $userService;
    }
    
    // CONSULTAS DE PRECIOS Y PAÍSES 

    public function getPaises(): void
    {
        try {
            $rol = $_GET['rol'] ?? 'Ambos';
            $paises = $this->pricingService->getCountriesByRole($rol);
            $this->sendJsonResponse($paises);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function getTasa(): void
    {
        try {
            $origenID = (int)($_GET['origenID'] ?? 0);
            $destinoID = (int)($_GET['destinoID'] ?? 0);

            $tasa = $this->pricingService->getCurrentRate($origenID, $destinoID);
            $this->sendJsonResponse($tasa);

        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 404); 
        }
    }

    // --- GESTIÓN DE CUENTAS DE BENEFICIARIO ---

    public function getCuentas(): void
    {
        $userId = $this->ensureLoggedIn();
        try {
            $paisID = (int)($_GET['paisID'] ?? 0);
            $cuentas = $this->beneficiaryService->getAccountsByCountry($userId, $paisID);
            $this->sendJsonResponse($cuentas);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function addCuenta(): void
    {
        $userId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput(); 
            $newId = $this->beneficiaryService->addAccount($userId, $data);
            $this->sendJsonResponse(['success' => true, 'id' => $newId], 201);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
    
    // --- FLUJO DE REMESAS Y ESTADOS ---

    public function createTransaccion(): void
    {
        $userId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput(); 
            $data['userID'] = $userId;

            $transactionId = $this->txService->createTransaction($data);
            $this->sendJsonResponse(['success' => true, 'transaccionID' => $transactionId], 201);

        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function cancelTransaction(): void
    {
        $userId = $this->ensureLoggedIn();
        try {
            $data = $this->getJsonInput(); 
            $this->txService->cancelTransaction($data['transactionId'] ?? 0, $userId);
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 409);
        }
    }

    public function uploadReceipt(): void
    {
        $userId = $this->ensureLoggedIn();
        $transactionId = $_POST['transactionId'] ?? 0;
        
        if (empty($transactionId) || !isset($_FILES['receiptFile'])) {
            $this->sendJsonResponse(['success' => false, 'error' => 'Datos incompletos o archivo no subido.'], 400);
        }

        $dbPath = 'uploads/receipts/tx_' . $transactionId . '_' . uniqid() . '.jpg';
        
        try {
            $this->txService->uploadUserReceipt($transactionId, $userId, $dbPath);
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // --- ACCIONES DE PERFIL Y VERIFICACIÓN ---

    public function getUserProfile(): void
    {
        $userId = $this->ensureLoggedIn();
        try {
            $profile = $this->userService->getUserProfile($userId);
            $this->sendJsonResponse(['success' => true, 'profile' => $profile]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function uploadVerificationDocs(): void
    {
        $userId = $this->ensureLoggedIn();
        
        try {
             $pathFrente = 'uploads/verifications/frente_' . $userId . '_' . uniqid() . '.jpg'; 
             $pathReverso = 'uploads/verifications/reverso_' . $userId . '_' . uniqid() . '.jpg'; 
             
             $this->userService->uploadVerificationDocs($userId, $pathFrente, $pathReverso);
             $this->sendJsonResponse(['success' => true, 'message' => 'Documentos subidos correctamente.']);
        } catch (Exception $e) {
             $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}