<?php

namespace App\Services;

use App\Repositories\RateRepository;
use App\Repositories\CountryRepository;
use Exception;

class PricingService
{
    private RateRepository $rateRepository;
    private CountryRepository $countryRepository;
    private NotificationService $notificationService;

    public function __construct(
        RateRepository $rateRepository,
        CountryRepository $countryRepository,
        NotificationService $notificationService 
    ) {
        $this->rateRepository = $rateRepository;
        $this->countryRepository = $countryRepository;
        $this->notificationService = $notificationService;
    }

    // LÓGICA DE LECTURA (Frontend y App Móvil)

    public function getCountriesByRole(string $role): array
    {
        $rolesValidos = ['Origen', 'Destino', 'Ambos'];
        if (!in_array($role, $rolesValidos)) {
            throw new Exception("Rol de país inválido.", 400);
        }
        return $this->countryRepository->findByRoleAndStatus($role, true);
    }

    public function getCurrentRate(int $origenID, int $destinoID): array
    {
        if ($origenID === $destinoID) {
            throw new Exception("El país de origen y destino no pueden ser iguales.", 400);
        }

        $tasaInfo = $this->rateRepository->findCurrentRate($origenID, $destinoID);

        if (!$tasaInfo) {
            throw new Exception("Ruta de remesa no configurada o inactiva.", 404);
        }
        
        return $tasaInfo;
    }

    // LÓGICA DE ADMINISTRACIÓN 

    public function adminAddCountry(int $adminId, string $nombrePais, string $codigoMoneda, string $rol): bool
    {
        $rolesValidos = ['Origen', 'Destino', 'Ambos'];
        if (empty($nombrePais) || strlen($codigoMoneda) !== 3 || !in_array($rol, $rolesValidos)) {
            throw new Exception("Datos de país incompletos o código de moneda inválido (3 letras).", 400);
        }

        try {
            $newId = $this->countryRepository->create($nombrePais, strtoupper($codigoMoneda), $rol);
            $this->notificationService->logAdminAction($adminId, 'Admin añadió país', "País: $nombrePais ($codigoMoneda)");
            return $newId > 0;
        } catch (Exception $e) {
            throw new Exception('Error al guardar el país. Asegúrese de que el nombre no esté duplicado.', 500); 
        }
    }
    
    public function adminUpdateCountryRole(int $adminId, int $paisId, string $newRole): bool
    {
        $rolesValidos = ['Origen', 'Destino', 'Ambos'];
        if (empty($paisId) || !in_array($newRole, $rolesValidos)) {
            throw new Exception("ID de país o rol no válido.", 400);
        }

        $success = $this->countryRepository->updateRole($paisId, $newRole);
        
        if ($success) {
            $this->notificationService->logAdminAction($adminId, "Admin cambió rol de país", "País ID: $paisId, Nuevo Rol: $newRole");
        }
        return $success;
    }

    public function adminToggleCountryStatus(int $adminId, int $paisId, bool $newStatus): bool
    {
        if (empty($paisId)) {
            throw new Exception("ID de país no válido.", 400);
        }

        $success = $this->countryRepository->updateStatus($paisId, $newStatus);
        
        if ($success) {
            $statusText = $newStatus ? 'Activado' : 'Desactivado';
            $this->notificationService->logAdminAction($adminId, "Admin cambió estado de país", "País ID: $paisId, Nuevo Estado: $statusText");
        }
        return $success;
    }

    public function adminUpdateRate(int $adminId, int $tasaId, float $nuevoValor): bool
    {
        if ($nuevoValor <= 0 || !is_numeric($nuevoValor)) {
            throw new Exception("El valor de la tasa debe ser un número positivo.", 400);
        }

        $success = $this->rateRepository->updateRateValue($tasaId, $nuevoValor);

        if ($success) {
            $this->notificationService->logAdminAction($adminId, 'Admin actualizó tasa', "Tasa ID: $tasaId, Nuevo Valor: $nuevoValor");
        }
        return $success;
    }
}