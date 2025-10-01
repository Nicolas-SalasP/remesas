<?php
namespace App\Services;

use App\Database\Database;
use Exception;

class DashboardService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getTransactionStatsForChart(string $startDate, string $endDate): array
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        if ($interval->days > 30) {
            throw new Exception("El rango de fechas no puede superar los 31 días.", 400);
        }

        $connection = $this->db->getConnection();
        $sql = "
            SELECT 
                DATE(FechaTransaccion) AS fecha,
                COUNT(TransaccionID) AS cantidad,
                SUM(MontoOrigen) AS volumen
            FROM transacciones
            WHERE DATE(FechaTransaccion) BETWEEN ? AND ?
            GROUP BY DATE(FechaTransaccion)
            ORDER BY fecha ASC
        ";

        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $labels = [];
        $dateRange = [];
        $currentDate = clone $start;
        while ($currentDate <= $end) {
            $dateKey = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('d/m');
            $dateRange[$dateKey] = ['cantidad' => 0, 'volumen' => 0];
            $currentDate->modify('+1 day');
        }

        foreach ($stats as $row) {
            if (isset($dateRange[$row['fecha']])) {
                $dateRange[$row['fecha']] = [
                    'cantidad' => (int)$row['cantidad'],
                    'volumen' => (float)$row['volumen']
                ];
            }
        }

        $transactionCounts = array_column(array_values($dateRange), 'cantidad');
        $volumeData = array_column(array_values($dateRange), 'volumen');

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cantidad de Transacciones',
                    'data' => $transactionCounts,
                    'borderColor' => '#007bff',
                    'backgroundColor' => 'rgba(0, 123, 255, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Volumen de Dinero (CLP)',
                    'data' => $volumeData,
                    'borderColor' => '#28a745',
                    'backgroundColor' => 'rgba(40, 167, 69, 0.1)',
                    'yAxisID' => 'y1',
                ]
            ]
        ];
    }
}