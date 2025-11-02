<?php
namespace App\Services;

use App\Repositories\RateRepository;
use App\Repositories\CountryRepository;
use App\Services\NotificationService;
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

    public function getCountriesByRole(string $role): array
    {
        $rolesValidos = ['Origen', 'Destino', 'Ambos'];
        if (!in_array($role, $rolesValidos)) {
            throw new Exception("Rol de país inválido.", 400);
        }
        return $this->countryRepository->findByRoleAndStatus($role, true);
    }

    public function getCurrentRate(int $origenID, int $destinoID, float $montoOrigen = 0): array
    {
        if ($origenID === $destinoID) {
            throw new Exception("El país de origen y destino no pueden ser iguales.", 400);
        }

        $tasaInfo = $this->rateRepository->findCurrentRate($origenID, $destinoID, $montoOrigen);

        if (!$tasaInfo) {
            $tasaInfo = $this->rateRepository->findCurrentRate($origenID, $destinoID, 0);

            if (!$tasaInfo) {
                throw new Exception("Ruta de remesa no configurada o inactiva.", 404);
            }
        }
        
        return $tasaInfo;
    }

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
    
    public function adminUpdateCountry(int $adminId, int $paisId, string $nombrePais, string $codigoMoneda): bool
    {
        if (empty($paisId) || empty($nombrePais) || strlen($codigoMoneda) !== 3) {
            throw new Exception("Datos de país incompletos o código de moneda inválido (3 letras).", 400);
        }

        try {
            $success = $this->countryRepository->update($paisId, $nombrePais, strtoupper($codigoMoneda));
            if ($success) {
                $this->notificationService->logAdminAction($adminId, 'Admin actualizó país', "País ID: $paisId, Nuevo Nombre: $nombrePais, Nueva Moneda: $codigoMoneda");
            }
            return $success;
        } catch (Exception $e) {
            throw new Exception('Error al actualizar el país. Asegúrese de que el nombre no esté duplicado.', 500);
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


    public function adminUpsertRate(int $adminId, array $data): array
    {
        $tasaId = $data['tasaId'] ?? 'new';
        $nuevoValor = (float)($data['nuevoValor'] ?? 0);
        $origenId = (int)($data['origenId'] ?? 0);
        $destinoId = (int)($data['destinoId'] ?? 0);
        $montoMin = (float)($data['montoMin'] ?? 0.00);
        $montoMax = (float)($data['montoMax'] ?? 9999999999.99);
        
        if ($montoMax == 0) $montoMax = 9999999999.99;

        if ($nuevoValor <= 0) {
            throw new Exception("El valor de la tasa debe ser un número positivo.", 400);
        }
        if ($origenId <= 0 || $destinoId <= 0) {
             throw new Exception("IDs de país de origen o destino inválidos.", 400);
        }

        $origenNombre = $this->countryRepository->findNameById($origenId) ?? "ID $origenId";
        $destinoNombre = $this->countryRepository->findNameById($destinoId) ?? "ID $destinoId";
        $rutaLog = "[$origenNombre -> $destinoNombre] Rango: [$montoMin - $montoMax]";

        $currentTasaId = 0;

        if ($tasaId === 'new') {
            $newTasaId = $this->rateRepository->createRate($origenId, $destinoId, $nuevoValor, $montoMin, $montoMax);
            $this->notificationService->logAdminAction($adminId, 'Admin creó tasa', "Ruta: $rutaLog, Valor: $nuevoValor, Nuevo TasaID: $newTasaId");
            $currentTasaId = $newTasaId;
        } else {
            $tasaIdInt = (int)$tasaId;
            $success = $this->rateRepository->updateRateValue($tasaIdInt, $nuevoValor, $montoMin, $montoMax);
            if ($success) {
                $this->notificationService->logAdminAction($adminId, 'Admin actualizó tasa', "Ruta: $rutaLog , Nuevo Valor: $nuevoValor");
            }
            $currentTasaId = $tasaIdInt;
        }

        if ($currentTasaId > 0) {
            $this->rateRepository->logRateChange($currentTasaId, $origenId, $destinoId, $nuevoValor, $montoMin, $montoMax);
        }

        return ['TasaID' => $currentTasaId];
    }
}