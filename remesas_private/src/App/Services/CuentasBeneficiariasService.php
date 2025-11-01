<?php
namespace App\Services;

use App\Repositories\CuentasBeneficiariasRepository;
use App\Repositories\TipoBeneficiarioRepository;
use App\Repositories\TipoDocumentoRepository;
use App\Services\NotificationService;
use Exception;

class CuentasBeneficiariasService
{
    private CuentasBeneficiariasRepository $cuentasBeneficiariasRepository;
    private NotificationService $notificationService;
    private TipoBeneficiarioRepository $tipoBeneficiarioRepo;
    private TipoDocumentoRepository $tipoDocumentoRepo;

    public function __construct(
        CuentasBeneficiariasRepository $cuentasBeneficiariasRepository,
        NotificationService $notificationService,
        TipoBeneficiarioRepository $tipoBeneficiarioRepo,
        TipoDocumentoRepository $tipoDocumentoRepo
    ) {
        $this->cuentasBeneficiariasRepository = $cuentasBeneficiariasRepository;
        $this->notificationService = $notificationService;
        $this->tipoBeneficiarioRepo = $tipoBeneficiarioRepo;
        $this->tipoDocumentoRepo = $tipoDocumentoRepo;
    }

    public function getAccountsByUser(int $userId, ?int $paisId = null): array
    {
        $cuentas = $this->cuentasBeneficiariasRepository->findByUserId($userId);

        if ($paisId !== null) {
            $cuentas = array_filter($cuentas, fn($cuenta) => isset($cuenta['PaisID']) && $cuenta['PaisID'] == $paisId);
        }

        return $cuentas;
    }

    public function getAccountDetails(int $userId, int $cuentaId): ?array
    {
        $cuenta = $this->cuentasBeneficiariasRepository->findByIdAndUserId($cuentaId, $userId);
        if (!$cuenta) {
            throw new Exception("Cuenta no encontrada o no te pertenece.", 404);
        }
        return $cuenta;
    }

    private function validateAndPrepareBeneficiaryData(array $data): array
    {
        $requiredFields = [
            'alias', 'tipoBeneficiario', 'primerNombre', 'primerApellido',
            'tipoDocumento', 'numeroDocumento', 'nombreBanco', 'numeroCuenta', 'numeroTelefono'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                throw new Exception("El campo '$field' es obligatorio y no puede estar vacío.", 400);
            }
        }

        $tipoBeneficiarioID = $this->tipoBeneficiarioRepo->findIdByName($data['tipoBeneficiario']);
        if (!$tipoBeneficiarioID) {
            error_log("TipoBeneficiario no encontrado en BD: " . $data['tipoBeneficiario']);
            throw new Exception("Tipo de beneficiario '{$data['tipoBeneficiario']}' no válido.", 400);
        }
        $data['tipoBeneficiarioID'] = $tipoBeneficiarioID;

        $tipoDocumentoID = $this->tipoDocumentoRepo->findIdByName($data['tipoDocumento']);
        if (!$tipoDocumentoID) {
            error_log("TipoDocumento no encontrado en BD: '" . $data['tipoDocumento'] . "'");
            throw new Exception("El tipo de documento seleccionado ('{$data['tipoDocumento']}') no es válido.", 400);
        }
        $data['titularTipoDocumentoID'] = $tipoDocumentoID;

        $data['segundoNombre'] = isset($data['segundoNombre']) && trim($data['segundoNombre']) !== '' ? trim($data['segundoNombre']) : null;
        $data['segundoApellido'] = isset($data['segundoApellido']) && trim($data['segundoApellido']) !== '' ? trim($data['segundoApellido']) : null;
        
        return $data;
    }

    public function addAccount(int $userId, array $data): int
    {
        $data = $this->validateAndPrepareBeneficiaryData($data);
        $data['UserID'] = $userId;
        
        if (!isset($data['paisID']) || empty($data['paisID'])) {
            throw new Exception("El campo 'paisID' es obligatorio.", 400);
        }

        try {
            $newId = $this->cuentasBeneficiariasRepository->create($data);
            $logAlias = $data['alias'] ?? 'N/A';
            $this->notificationService->logAdminAction($userId, 'Usuario añadió cuenta beneficiaria', "Alias: {$logAlias} - ID: {$newId}");
            return $newId;
        } catch (Exception $e) {
             error_log("Error al crear cuenta beneficiaria en CuentasBeneficiariasRepository: " . $e->getMessage());
            throw new Exception("Error al guardar la cuenta del beneficiario en la base de datos.", $e->getCode() ?: 500);
        }
    }
    
    public function updateAccount(int $userId, int $cuentaId, array $data): bool
    {
        $data = $this->validateAndPrepareBeneficiaryData($data);
        
        try {
            $success = $this->cuentasBeneficiariasRepository->update($cuentaId, $userId, $data);
            $logAlias = $data['alias'] ?? 'N/A';
            $this->notificationService->logAdminAction($userId, 'Usuario actualizó beneficiario', "Alias: {$logAlias} - ID: {$cuentaId}");
            return $success;
        } catch (Exception $e) {
             error_log("Error al actualizar cuenta beneficiaria: " . $e->getMessage());
            throw new Exception("Error al actualizar la cuenta del beneficiario.", $e->getCode() ?: 500);
        }
    }
    
    public function deleteAccount(int $userId, int $cuentaId): bool
    {
        try {
            $success = $this->cuentasBeneficiariasRepository->delete($cuentaId, $userId);
            if ($success) {
                $this->notificationService->logAdminAction($userId, 'Usuario eliminó beneficiario', "ID: {$cuentaId}");
            }
            return $success;
        } catch (Exception $e) {
             error_log("Error al eliminar cuenta beneficiaria: " . $e->getMessage());
            throw new Exception($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}