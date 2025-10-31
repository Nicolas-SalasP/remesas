<?php

require_once __DIR__ . '/../core/init.php';

echo "Iniciando calentamiento de caché para el gráfico...\n";

$ranges = [7, 30, 180, 365];

foreach ($ranges as $range) {
    echo "Procesando rango de {$range} dias... ";

    $cacheDir = __DIR__ . '/../cache/';
    $cacheFile = $cacheDir . 'rates_history_' . $range . '_days.json';

    $startDate = date('Y-m-d', strtotime("-$range days"));
    $endDate = date('Y-m-d', strtotime("-1 day"));
    
    if ($startDate >= $endDate) {
        $startDate = date('Y-m-d', strtotime("-2 days"));
    }
    
    $apiUrl = "https://api.frankfurter.app/{$startDate}..{$endDate}?to=VES&from=USD";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'JCEnviosApp/1.0 (CacheWarmer)');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response && $httpCode === 200) {
        $data = json_decode($response, true);
        if (!empty($data['rates'])) {
            ksort($data['rates']);
            $labels = [];
            $dataPoints = [];
            foreach ($data['rates'] as $date => $rates) {
                if (isset($rates['VES'])) {
                    $labels[] = date("d/m", strtotime($date));
                    $dataPoints[] = $rates['VES'];
                }
            }
            
            if (empty($dataPoints)) {
                echo "¡FALLO! La API devolvió datos pero sin la moneda 'VES'.\n";
                continue;
            }
            
            $output = json_encode([
                'success' => true,
                'valorActual' => end($dataPoints),
                'lastUpdate' => date('Y-m-d H:i:s'),
                'labels' => $labels,
                'dataPoints' => $dataPoints
            ]);

            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            
            file_put_contents($cacheFile, $output);
            echo "¡OK!\n";
        } else {
            echo "¡FALLO! No se recibieron datos de 'rates' en la respuesta de la API (JSON inválido o vacío).\n";
        }
    } else {
        echo "¡FALLO! No se pudo conectar con la API externa. HTTP Code: $httpCode. Error cURL: $curlError\n";
    }
}

echo "Calentamiento de caché finalizado.\n";
?>