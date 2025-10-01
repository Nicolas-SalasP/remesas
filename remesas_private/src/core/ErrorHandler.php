<?php

namespace App\Core;

use Exception;

function exception_handler(Exception $exception): void
{
    error_reporting(0);
    ini_set('display_errors', 0);

    $statusCode = $exception->getCode();
    if ($statusCode < 400 || $statusCode >= 600) {
        $statusCode = 500; 
    }

    $response = [
        'success' => false,
        'error' => $exception->getMessage()
    ];

   
    if (IS_DEV_ENVIRONMENT) {
        $response['trace'] = $exception->getTraceAsString();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($response);

    error_log(
        "Excepción no controlada: " . $exception->getMessage() .
        " en " . $exception->getFile() .
        " línea " . $exception->getLine()
    );

    exit();
}