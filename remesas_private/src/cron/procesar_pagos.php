<?php
if (php_sapi_name() !== 'cli') {
    die("Acceso denegado. Este script solo puede ejecutarse desde la línea de comandos (CLI).");
}

define('IS_CRON', true);
set_time_limit(300);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../core/init.php';

use App\Database\Database;
use App\Repositories\TransactionRepository;
use App\Services\LogService;
use App\Services\NotificationService;
use App\Services\FileHandlerService;
use App\Services\EmailReconciliationService;

try {
    echo "--------------------------------------------------\n";
    echo "[" . date('Y-m-d H:i:s') . "] Iniciando BOT de Conciliación Bancaria...\n";

    $db = Database::getInstance();

    $txRepo = new TransactionRepository($db);
    $logService = new LogService($db);
    $notifService = new NotificationService($logService);
    $fileHandler = new FileHandlerService();
    
    $bot = new EmailReconciliationService($txRepo, $notifService, $fileHandler);

    $bot->procesarCorreosNoLeidos();
    
    echo "[" . date('Y-m-d H:i:s') . "] Proceso finalizado con éxito.\n";
    echo "--------------------------------------------------\n";

} catch (\Throwable $e) {
    $msg = "ERROR FATAL EN CRON: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine();
    echo $msg . "\n";
    error_log($msg);
}