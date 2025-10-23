<?php
namespace App\Controllers;

use App\Services\DashboardService;
use Exception;

class DashboardController extends BaseController
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function getStats(): void
    {
        try {
            $stats = $this->dashboardService->getAdminDashboardStats();
            $this->sendJsonResponse(['success' => true, 'stats' => $stats]);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function getDolarBcvData(): void
    {
        try {
            $data = $this->dashboardService->getDolarBcvData();
             if (!isset($data['success']) || !$data['success']) {
                 throw new Exception($data['error'] ?? 'Error desconocido al obtener datos del dólar.');
             }
            $this->sendJsonResponse($data);
        } catch (Exception $e) {
             error_log("Error en DashboardController::getDolarBcvData: " . $e->getMessage());
            $this->sendJsonResponse(['success' => false, 'error' => 'No se pudieron obtener los datos del gráfico en este momento.'], $e->getCode() >= 400 ? $e->getCode() : 500);
        }
    }
}