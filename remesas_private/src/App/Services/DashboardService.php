<?php
namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\RateRepository;
use App\Repositories\EstadoTransaccionRepository;
use App\Repositories\CountryRepository;
use Exception;

class DashboardService
{
    private TransactionRepository $transactionRepository;
    private UserRepository $userRepository;
    private RateRepository $rateRepository;
    private EstadoTransaccionRepository $estadoTxRepo;
    private CountryRepository $countryRepository;

    private const ESTADO_EN_VERIFICACION = 'En Verificación';
    private const ESTADO_EN_PROCESO = 'En Proceso';
    private const ESTADO_PAGADO = 'Pagado';
    private const ESTADO_PENDIENTE = 'Pendiente de Pago';

    public function __construct(
        TransactionRepository $transactionRepository,
        UserRepository $userRepository,
        RateRepository $rateRepository,
        EstadoTransaccionRepository $estadoTxRepo,
        CountryRepository $countryRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $userRepository;
        $this->rateRepository = $rateRepository;
        $this->estadoTxRepo = $estadoTxRepo;
        $this->countryRepository = $countryRepository;
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
        $estadoPendienteID = $this->getEstadoId(self::ESTADO_PENDIENTE); 
        
        $pendingTransactions = $this->transactionRepository->countByStatus([
            $estadoVerificacionID, 
            $estadoEnProcesoID,
            $estadoPendienteID
        ]);

        $topDestino = $this->transactionRepository->getTopCountries('Destino', 5);
        $topOrigen = $this->transactionRepository->getTopCountries('Origen', 5);
        $txStats = $this->transactionRepository->getTransactionStats();
        $topUsers = $this->transactionRepository->getTopUsers(5);

        $formatChartData = function(array $data): array {
            return [
                'labels' => array_column($data, 'NombrePais'),
                'data' => array_column($data, 'Total')
            ];
        };

        return [
            'kpis' => [
                'totalUsers' => $totalUsers,
                'pendingTransactions' => $pendingTransactions,
                'averageDaily' => (float)number_format($txStats['PromedioDiario'], 2),
                'busiestMonth' => $txStats['MesMasConcurrido'] . ' (' . $txStats['TotalMesMasConcurrido'] . ' trans.)'
            ],
            'charts' => [
                'topDestino' => $formatChartData($topDestino),
                'topOrigen' => $formatChartData($topOrigen)
            ],
            'tables' => [
                'topUsers' => $topUsers
            ]
        ];
    }

    public function getDolarBcvData(int $origenId, int $destinoId, int $days = 30): array
    {
        $history = $this->transactionRepository->getRateHistoryByDate($origenId, $destinoId, $days);
        $currentRateInfo = $this->rateRepository->findCurrentRate($origenId, $destinoId);
        $valorActual = (float)($currentRateInfo['ValorTasa'] ?? 0);
        $monedaOrigen = $this->countryRepository->findMonedaById($origenId) ?? 'N/A';
        $monedaDestino = $this->countryRepository->findMonedaById($destinoId) ?? 'N/A';

        $labels = [];
        $dataPoints = [];

        if (empty($history)) {
            $labels[] = date("d/m");
            $dataPoints[] = $valorActual;
        } else {
            foreach ($history as $row) {
                $labels[] = date("d/m", strtotime($row['Fecha']));
                $dataPoints[] = (float)$row['TasaPromedio'];
            }
        }
        
        if (end($dataPoints) !== $valorActual && $valorActual > 0) {
             $labels[] = date("d/m");
             $dataPoints[] = $valorActual;
        }

        $output = [
            'success' => true,
            'valorActual' => $valorActual,
            'monedaOrigen' => $monedaOrigen,
            'monedaDestino' => $monedaDestino,
            'lastUpdate' => date('Y-m-d H:i:s'),
            'labels' => $labels,
            'data' => $dataPoints
        ];

        return $output;
    }
}