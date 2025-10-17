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
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getDolarBcvData(): void
    {
        try {
            $data = $this->dashboardService->getDolarBcvData();
            $this->sendJsonResponse($data);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => 'No se pudieron obtener los datos del gráfico en este momento.'], 500);
        }
    }
}

