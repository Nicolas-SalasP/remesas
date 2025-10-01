<?php
namespace App\Controllers;

use App\Services\DashboardService;

class DashboardController extends BaseController
{
    private DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
        $this->ensureAdmin(); 
    }

    public function getStats(): void
    {
        $endDate = $_GET['fecha_fin'] ?? date('Y-m-d');
        $startDate = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-6 days', strtotime($endDate)));

        $stats = $this->dashboardService->getTransactionStatsForChart($startDate, $endDate);
        $this->sendJsonResponse(['success' => true, 'stats' => $stats]);
    }
}