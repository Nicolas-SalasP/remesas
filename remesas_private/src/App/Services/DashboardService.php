<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\RateRepository;
use Exception;

class DashboardService
{
    private TransactionRepository $transactionRepository;
    private UserRepository $userRepository;
    private RateRepository $rateRepository;

    public function __construct(
        TransactionRepository $transactionRepository, 
        UserRepository $userRepository,
        RateRepository $rateRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->rateRepository = $rateRepository;
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
        if (!is_dir($cacheDir)) {
            if (!@mkdir($cacheDir, 0755, true)) {
                 throw new Exception("Error de permisos: No se pudo crear el directorio de caché.", 500);
            }
        }
        
        $cacheFile = $cacheDir . 'dolar_bcv_history.json';
        $cacheTime = 3600; 

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
            $cachedData = file_get_contents($cacheFile);
            return json_decode($cachedData, true);
        }

        $startDate = date('Y-m-d', strtotime("-7 days"));
        $apiUrl = "https://api.frankfurter.app/{$startDate}..?to=VES&from=USD";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch) || $httpCode !== 200) {
            curl_close($ch);
            throw new Exception("No se pudo conectar con la API de tasas de cambio. " . curl_error($ch), 503);
        }
        curl_close($ch);

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

        if (is_writable($cacheDir)) {
            file_put_contents($cacheFile, json_encode($output));
        }

        return $output;
    }
}

