<?php
namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\RateRepository;
use App\Repositories\EstadoTransaccionRepository;
use Exception;

class DashboardService
{
    private TransactionRepository $transactionRepository;
    private UserRepository $userRepository;
    private RateRepository $rateRepository;
    private EstadoTransaccionRepository $estadoTxRepo;

    private const ESTADO_EN_VERIFICACION = 'En Verificación';
    private const ESTADO_EN_PROCESO = 'En Proceso';
    private const ESTADO_PAGADO = 'Pagado';

    public function __construct(
        TransactionRepository $transactionRepository,
        UserRepository $userRepository,
        RateRepository $rateRepository,
        EstadoTransaccionRepository $estadoTxRepo
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->rateRepository = $rateRepository;
        $this->estadoTxRepo = $estadoTxRepo;
    }

    private function getEstadoId(string $nombreEstado): int
    {
        $id = $this->estadoTxRepo->findIdByName($nombreEstado);
        if ($id === null) {
            throw new Exception("Configuración interna: Estado de transacción '{$nombreEstado}' no encontrado.", 500);
        }
        return $id;
    }

    public function getAdminDashboardStats(): array
    {
        $totalUsers = $this->userRepository->countAll();

        $estadoVerificacionID = $this->getEstadoId(self::ESTADO_EN_VERIFICACION);
        $estadoEnProcesoID = $this->getEstadoId(self::ESTADO_EN_PROCESO);
        $pendingTransactions = $this->transactionRepository->countByStatus([$estadoVerificacionID, $estadoEnProcesoID]);

        $estadoPagadoID = $this->getEstadoId(self::ESTADO_PAGADO);
        $completedToday = $this->transactionRepository->countCompletedToday($estadoPagadoID);
        $totalVolume = $this->transactionRepository->getTotalVolume($estadoPagadoID);

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
                 error_log("Error de permisos: No se pudo crear el directorio de caché: {$cacheDir}");
                 throw new Exception("Error interno al preparar la caché.", 500);
            }
        }
         if (!is_writable($cacheDir)) {
             error_log("Error de permisos: El directorio de caché no tiene permisos de escritura: {$cacheDir}");
             throw new Exception("Error interno de permisos de caché.", 500);
         }

        $cacheFile = $cacheDir . 'bcv_current_rate.json';
        $cacheTime = 3600; 

        $decodedData = null;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
            $cachedData = @file_get_contents($cacheFile);
             if ($cachedData) {
                 $decodedData = json_decode($cachedData, true);
                 if (json_last_error() === JSON_ERROR_NONE && isset($decodedData['success']) && $decodedData['success']) {
                     return $decodedData; 
                 }
             }
        }

        $apiUrl = "https://api.monitordolarvenezuela.com/last_update_bcv";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'JCEnviosApp/1.0 (Compatible; PHP cURL)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200 || $response === false) {
             error_log("Error al conectar con API BCV (monitordolar): HTTP {$httpCode} - Error: {$curlError} - URL: {$apiUrl}");
             if ($decodedData) {
                 error_log("Devolviendo datos cacheados antiguos de BCV debido a error de API.");
                 return $decodedData;
             }
            throw new Exception("No se pudo obtener la tasa de cambio externa (BCV) en este momento.", 503);
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['status']) || $data['status'] !== 'success' || empty($data['bcv']['price'])) {
            error_log("Respuesta inválida de API BCV (monitordolar): " . json_last_error_msg() . " - Respuesta: " . substr($response, 0, 500));
            if ($decodedData) {
                 error_log("Devolviendo datos cacheados antiguos de BCV debido a respuesta inválida.");
                 return $decodedData;
            }
            throw new Exception("La API externa (BCV) devolvió datos inválidos.", 502);
        }

        $valorActual = $data['bcv']['price'];

        $output = [
            'success' => true,
            'valorActual' => (float)$valorActual,
            'lastUpdate' => $data['bcv']['last_update'] ?? date('Y-m-d H:i:s')
        ];
        
        if (!@file_put_contents($cacheFile, json_encode($output))) {
             error_log("Error al escribir en el archivo de caché de BCV: {$cacheFile}");
        }

        return $output;
    }
}