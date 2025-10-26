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
            $cuentas = array_filter($cuentas, fn($cuenta) => $cuenta['PaisID'] == $paisId);
        }

        return array_map(fn($cuenta) => [
            'CuentaID' => $cuenta['CuentaID'],
            'Alias' => $cuenta['Alias'],
        ], array_values($cuentas));
    }

    public function addAccount(int $userId, array $data): int
    {
        $requiredFields = [
            'paisID', 'alias', 'tipoBeneficiario', 'primerNombre', 'primerApellido',
            'tipoDocumento', 'numeroDocumento', 'nombreBanco', 'numeroCuenta', 'numeroTelefono'
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo '$field' es obligatorio.", 400);
            }
        }

        $tipoBeneficiarioID = $this->tipoBeneficiarioRepo->findIdByName($data['tipoBeneficiario']);
        if (!$tipoBeneficiarioID) {
            throw new Exception("Tipo de beneficiario '{$data['tipoBeneficiario']}' no válido.", 400);
        }
        $data['tipoBeneficiarioID'] = $tipoBeneficiarioID;

        $tipoDocumentoID = $this->tipoDocumentoRepo->findIdByName($data['tipoDocumento']);
        if (!$tipoDocumentoID) {
            throw new Exception("Tipo de documento '{$data['tipoDocumento']}' no válido.", 400);
        }
        $data['titularTipoDocumentoID'] = $tipoDocumentoID;

        $data['UserID'] = $userId;

        try {
            $newId = $this->cuentasBeneficiariasRepository->create($data);
            $this->notificationService->logAdminAction($userId, 'Usuario añadió cuenta beneficiaria', "Alias: " . ($data['alias'] ?? 'N/A') . " - ID: {$newId}");
            return $newId;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}