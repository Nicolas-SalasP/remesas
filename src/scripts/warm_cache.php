<?php

require_once __DIR__ . '/../core/init.php';

echo "Iniciando calentamiento de caché para el gráfico...\n";

$ranges = [7, 30, 180, 365];

foreach ($ranges as $range) {
    echo "Procesando rango de {$range} dias... ";

    $cacheDir = __DIR__ . '/../cache/';
    $cacheFile = $cacheDir . 'rates_history_' . $range . '_days.json';

    $startDate = date('Y-m-d', strtotime("-$range days"));
    $apiUrl = "https://api.frankfurter.app/{$startDate}..?to=VES&from=USD";
    
    $response = @file_get_contents($apiUrl);
    
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['rates'])) {
            ksort($data['rates']);
            $labels = [];
            $dataPoints = [];
            foreach ($data['rates'] as $date => $rates) {
                $labels[] = date("d/m", strtotime($date));
                $dataPoints[] = $rates['VES'];
            }
            
            $output = json_encode([
                'success' => true,
                'valorActual' => end($dataPoints),
                'labels' => $labels,
                'dataPoints' => $dataPoints
            ]);

            file_put_contents($cacheFile, $output);
            echo "¡OK!\n";
        } else {
            echo "¡FALLO! No se recibieron datos de la API.\n";
        }
    } else {
        echo "¡FALLO! No se pudo conectar con la API externa.\n";
    }
}

echo "Calentamiento de caché finalizado.\n";
?>