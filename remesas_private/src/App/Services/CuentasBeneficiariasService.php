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

        return array_map(fn($cuenta) => [
            'CuentaID' => $cuenta['CuentaID'],
            'Alias' => $cuenta['Alias'] ?? 'Sin Alias',
        ], array_values($cuentas));
    }

    public function addAccount(int $userId, array $data): int
    {
        $requiredFields = [
            'paisID', 'alias', 'tipoBeneficiario', 'primerNombre', 'primerApellido',
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

        $receivedDocTypeName = $data['tipoDocumento'] ?? '[No recibido]';
        error_log("CuentasBeneficiariasService::addAccount - Intentando buscar TipoDocumentoID para: '" . $receivedDocTypeName . "'");

        $tipoDocumentoID = $this->tipoDocumentoRepo->findIdByName($data['tipoDocumento']);
        if (!$tipoDocumentoID) {
            error_log("TipoDocumento no encontrado en BD: '" . $receivedDocTypeName . "'");
            throw new Exception("El tipo de documento seleccionado ('{$data['tipoDocumento']}') no es válido o no está configurado correctamente.", 400);
        }
        $data['titularTipoDocumentoID'] = $tipoDocumentoID;

        $data['UserID'] = $userId;

        $data['segundoNombre'] = isset($data['segundoNombre']) && trim($data['segundoNombre']) !== '' ? trim($data['segundoNombre']) : null;
        $data['segundoApellido'] = isset($data['segundoApellido']) && trim($data['segundoApellido']) !== '' ? trim($data['segundoApellido']) : null;

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
}