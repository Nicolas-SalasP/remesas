<?php
namespace App\Controllers;

use App\Services\TransactionService;
use App\Services\PricingService;
use App\Services\CuentasBeneficiariasService;
use App\Services\UserService;
use App\Repositories\FormaPagoRepository;
use App\Repositories\TipoBeneficiarioRepository;
use App\Repositories\TipoDocumentoRepository;
use App\Services\FileHandlerService; 
use Exception;

class ClientController extends BaseController
{
    private TransactionService $txService;
    private PricingService $pricingService;
    private CuentasBeneficiariasService $cuentasBeneficiariasService;
    private UserService $userService;
    private FormaPagoRepository $formaPagoRepo;
    private TipoBeneficiarioRepository $tipoBeneficiarioRepo;
    private TipoDocumentoRepository $tipoDocumentoRepo;
    private FileHandlerService $fileHandler; 

    public function __construct(
        TransactionService $txService,
        PricingService $pricingService,
        CuentasBeneficiariasService $cuentasBeneficiariasService,
        UserService $userService,
        FormaPagoRepository $formaPagoRepo,
        TipoBeneficiarioRepository $tipoBeneficiarioRepo,
        TipoDocumentoRepository $tipoDocumentoRepo,
        FileHandlerService $fileHandler 
    ) {
        $this->txService = $txService;
        $this->pricingService = $pricingService;
        $this->cuentasBeneficiariasService = $cuentasBeneficiariasService;
        $this->userService = $userService;
        $this->formaPagoRepo = $formaPagoRepo;
        $this->tipoBeneficiarioRepo = $tipoBeneficiarioRepo;
        $this->tipoDocumentoRepo = $tipoDocumentoRepo;
        $this->fileHandler = $fileHandler; 
    }

    public function getPaises(): void
    {
        $rol = $_GET['rol'] ?? 'Ambos';
        $paises = $this->pricingService->getCountriesByRole($rol);
        $this->sendJsonResponse($paises);
    }

    public function getTasa(): void
    {
        $origenID = (int)($_GET['origenID'] ?? 0);
        $destinoID = (int)($_GET['destinoID'] ?? 0);
        $tasa = $this->pricingService->getCurrentRate($origenID, $destinoID);
        $this->sendJsonResponse($tasa);
    }

    public function getFormasDePago(): void
    {
        $formasPago = $this->formaPagoRepo->findAllActive();
        $nombres = array_column($formasPago, 'Nombre');
        $this->sendJsonResponse($nombres);
    }

    public function getBeneficiaryTypes(): void
    {
        $tipos = $this->tipoBeneficiarioRepo->findAllActive();
        $nombres = array_column($tipos, 'Nombre');
        $this->sendJsonResponse($nombres);
    }

     public function getDocumentTypes(): void
    {
        $tipos = $this->tipoDocumentoRepo->findAllActive();
        $response = array_map(fn($tipo) => ['id' => $tipo['TipoDocumentoID'], 'nombre' => $tipo['NombreDocumento']], $tipos);
        $this->sendJsonResponse($response);
    }

    public function getCuentas(): void
    {
        $userId = $this->ensureLoggedIn();
        $paisID = (int)($_GET['paisID'] ?? 0);
        $cuentas = $this->cuentasBeneficiariasService->getAccountsByUser($userId, $paisID ?: null); 
        $this->sendJsonResponse($cuentas);
    }

    public function addCuenta(): void
    {
        $userId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $newId = $this->cuentasBeneficiariasService->addAccount($userId, $data);
        $this->sendJsonResponse(['success' => true, 'id' => $newId], 201);
    }

    public function createTransaccion(): void
    {
        $userId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $data['userID'] = $userId;
        $transactionId = $this->txService->createTransaction($data);
        $this->sendJsonResponse(['success' => true, 'transaccionID' => $transactionId], 201);
    }

    public function cancelTransaction(): void
    {
        $userId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        $this->txService->cancelTransaction($data['transactionId'] ?? 0, $userId);
        $this->sendJsonResponse(['success' => true]);
    }

    public function uploadReceipt(): void
    {
        $userId = $this->ensureLoggedIn();
        $transactionId = (int)($_POST['transactionId'] ?? 0);

        if ($transactionId <= 0 || !isset($_FILES['receiptFile'])) {
            throw new Exception('ID de transacción inválido o archivo no subido.', 400);
        }

        try {
             $directory = 'receipts'; 
             $filenamePrefix = 'tx_recibo_' . $transactionId;
             $dbPath = $this->fileHandler->handleGenericUpload($_FILES['receiptFile'], $directory, $filenamePrefix);

            $this->txService->uploadUserReceipt($transactionId, $userId, $dbPath);
            $this->sendJsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }


    public function getUserProfile(): void
    {
        $userId = $this->ensureLoggedIn();
        $profile = $this->userService->getUserProfile($userId);
        $this->sendJsonResponse(['success' => true, 'profile' => $profile]);
    }

    public function uploadVerificationDocs(): void
    {
        $userId = $this->ensureLoggedIn();
        $this->userService->uploadVerificationDocs($userId, $_FILES);
        $this->sendJsonResponse(['success' => true, 'message' => 'Documentos subidos correctamente. Serán revisados.']);
    }
}