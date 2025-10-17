<?php

namespace App\Core;

function exception_handler(\Throwable $exception): void
{
    error_reporting(0);
    ini_set('display_errors', 0);

    $statusCode = method_exists($exception, 'getCode') ? $exception->getCode() : 500;
    if ($statusCode < 400 || $statusCode >= 600) {
        $statusCode = 500; 
    }

    $response = [
        'success' => false,
        'error' => 'Ocurrió un error inesperado en el servidor.'
    ];

    if (defined('IS_DEV_ENVIRONMENT') && IS_DEV_ENVIRONMENT) {
        $response['error'] = $exception->getMessage();
        $response['trace'] = explode("\n", $exception->getTraceAsString());
        $response['file'] = $exception->getFile() . ':' . $exception->getLine();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($response);

    error_log(
        "Excepción no controlada: " . $exception->getMessage() .
        " en " . $exception->getFile() .
        " en la línea " . $exception->getLine()
    );

    exit();
}