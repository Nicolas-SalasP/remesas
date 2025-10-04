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

    public function getCurrentRate(int $origenID, int $destinoID): ?array
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

    public function getCountriesByRole(string $role): array
    {
        return $this->countryRepository->findByRoleAndStatus($role);
    }
    
    public function adminUpdateRate(int $adminId, int $tasaId, float $nuevoValor): bool
    {
        if ($nuevoValor <= 0) {
            throw new Exception("El valor de la tasa debe ser positivo.", 400);
        }

        $success = $this->rateRepository->updateRateValue($tasaId, $nuevoValor);

        if ($success) {
            $this->notificationService->logAdminAction($adminId, 'Admin actualizó tasa', "Tasa ID: $tasaId, Nuevo Valor: $nuevoValor");
        }
        return $success;
    }
}