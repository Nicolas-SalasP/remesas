<?php

namespace App\Services;

use App\Repositories\BeneficiaryRepository;
use Exception;

class BeneficiaryService
{
    private BeneficiaryRepository $beneficiaryRepository;
    private NotificationService $notificationService;

    public function __construct(
        BeneficiaryRepository $beneficiaryRepository,
        NotificationService $notificationService 
    ) {
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->notificationService = $notificationService;
    }

    // LÓGICA DE CONSULTA 

    public function getAccountsByCountry(int $userId, int $paisId): array
    {
        if (empty($paisId) || $paisId <= 0) {
            throw new Exception("El ID del país es requerido o inválido.", 400);
        }

        return $this->beneficiaryRepository->findAccountsByUserIdAndCountry($userId, $paisId);
    }

    // LÓGICA DE ESCRITURA 

    public function addAccount(int $userId, array $data): int
    {
        $requiredFields = [
            'paisID', 'alias', 'tipoBeneficiario', 'primerNombre', 'primerApellido', 
            'tipoDocumento', 'numeroDocumento', 'nombreBanco', 'numeroCuenta'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("El campo '$field' es obligatorio.", 400);
            }
        }
        
        // 2. Lógica de Negocio/Validación Avanzada (Ejemplo de donde iría)
        // ** Aquí se integraría la validación de la longitud y formato de Teléfono/Cuenta/Documento **
        // if (!$this->validateAccountDetails($data)) { 
        //     throw new Exception("Error de formato en teléfono o número de cuenta.", 400);
        // }

        $data['UserID'] = $userId;
        
        try {
            $newId = $this->beneficiaryRepository->create($data);
            $this->notificationService->logAdminAction($userId, 'Usuario añadió cuenta beneficiaria', "Alias: " . $data['alias']);
            return $newId;
        } catch (Exception $e) {
            throw new Exception('Error al guardar la cuenta: ' . $e->getMessage(), 500);
        }
    }
}