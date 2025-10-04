<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use Exception;

class DashboardService
{
    private TransactionRepository $transactionRepository;
    private UserRepository $userRepository;

    public function __construct(TransactionRepository $transactionRepository, UserRepository $userRepository)
    {
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
    }

    public function getAdminDashboardStats(): array
    {
        $totalUsers = $this->userRepository->countAll();
        $pendingTransactions = $this->transactionRepository->countByStatus(['En Verificación', 'En Proceso']);
        $completedToday = $this->transactionRepository->countCompletedToday();
        $totalVolume = $this->transactionRepository->getTotalVolume();

        return [
            'totalUsers' => $totalUsers,
            'pendingTransactions' => $pendingTransactions,
            'completedToday' => $completedToday,
            'totalVolume' => number_format($totalVolume, 2, ',', '.') . ' CLP'
        ];
    }

    public function getDolarBcvData(): array
    {
        $cacheDir = __DIR__ . '/../../cache/';
        $cacheFile = $cacheDir . 'dolar_bcv_history.json';
        $cacheTime = 3600;

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        $startDate = date('Y-m-d', strtotime("-7 days"));
        $apiUrl = "https://api.frankfurter.app/{$startDate}..?to=VES&from=USD";
        
        $response = @file_get_contents($apiUrl);

        if ($response === false) {
            throw new Exception("No se pudo conectar con la API de tasas de cambio.", 503);
        }

        $data = json_decode($response, true);
        if (empty($data['rates'])) {
            throw new Exception("La API de tasas de cambio no devolvió datos válidos.", 500);
        }
        
        ksort($data['rates']);
        $labels = [];
        $dataPoints = [];
        foreach ($data['rates'] as $date => $rates) {
            $labels[] = date("d/m", strtotime($date));
            $dataPoints[] = $rates['VES'];
        }
        
        $output = [
            'success' => true,
            'valorActual' => end($dataPoints),
            'labels' => $labels,
            'dataPoints' => $dataPoints
        ];

        file_put_contents($cacheFile, json_encode($output));

        return $output;
    }
}