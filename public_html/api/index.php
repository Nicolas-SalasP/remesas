<?php

require_once __DIR__ . '/../../remesas_private/src/core/init.php';

use App\Database\Database;
use App\Repositories\UserRepository;
use App\Repositories\RateRepository;
use App\Repositories\CountryRepository;
use App\Repositories\BeneficiaryRepository;
use App\Repositories\TransactionRepository;
use App\Services\LogService;
use App\Services\NotificationService;
use App\Services\PDFService;
use App\Services\FileHandlerService;
use App\Services\UserService;
use App\Services\PricingService;
use App\Services\TransactionService;
use App\Services\BeneficiaryService;
use App\Services\DashboardService;
use App\Controllers\AuthController;
use App\Controllers\ClientController;
use App\Controllers\AdminController;
use App\Controllers\DashboardController;

header('Content-Type: application/json');

try {
    $db = Database::getInstance();

    $userRepository = new UserRepository($db);
    $rateRepository = new RateRepository($db);
    $countryRepository = new CountryRepository($db);
    $beneficiaryRepository = new BeneficiaryRepository($db);
    $transactionRepository = new TransactionRepository($db);
    
    $logService = new LogService($db); 
    $notificationService = new NotificationService($logService); 
    $pdfService = new PDFService();
    $fileHandler = new FileHandlerService();
    
    $userService = new UserService($userRepository, $notificationService, $fileHandler);
    $pricingService = new PricingService($rateRepository, $countryRepository, $notificationService);
    $beneficiaryService = new BeneficiaryService($beneficiaryRepository, $notificationService);
    $transactionService = new TransactionService($transactionRepository, $userRepository, $notificationService, $pdfService, $fileHandler);
    $dashboardService = new DashboardService($transactionRepository, $userRepository, $rateRepository);

} catch (\Throwable $e) {
    \App\Core\exception_handler($e);
}

$accion = $_GET['accion'] ?? '';

$routes = [
    // Rutas Públicas
    'loginUser'             => [AuthController::class, 'loginUser'],
    'registerUser'          => [AuthController::class, 'registerUser'],
    'requestPasswordReset'  => [AuthController::class, 'requestPasswordReset'],
    'performPasswordReset'  => [AuthController::class, 'performPasswordReset'],
    'getTasa'               => [ClientController::class, 'getTasa'],
    'getPaises'             => [ClientController::class, 'getPaises'],
    'getDolarBcv'           => [DashboardController::class, 'getDolarBcvData'],

    // Rutas de Cliente (Requieren Login)
    'getCuentas'            => [ClientController::class, 'getCuentas'],
    'addCuenta'             => [ClientController::class, 'addCuenta'],
    'createTransaccion'     => [ClientController::class, 'createTransaccion'],
    'cancelTransaction'     => [ClientController::class, 'cancelTransaction'],
    'uploadReceipt'         => [ClientController::class, 'uploadReceipt'],
    'getUserProfile'        => [ClientController::class, 'getUserProfile'],
    'uploadVerificationDocs'=> [ClientController::class, 'uploadVerificationDocs'],
    
    // Rutas de Admin (Requieren Login y Rol de Admin)
    'updateRate'            => [AdminController::class, 'updateRate'],
    'addPais'               => [AdminController::class, 'addPais'],
    'updatePaisRol'         => [AdminController::class, 'updatePaisRol'],
    'togglePaisStatus'      => [AdminController::class, 'togglePaisStatus'],
    'processTransaction'    => [AdminController::class, 'processTransaction'],
    'rejectTransaction'     => [AdminController::class, 'rejectTransaction'],
    'adminUploadProof'      => [AdminController::class, 'adminUploadProof'],
    'updateVerificationStatus' => [AdminController::class, 'updateVerificationStatus'],
    'toggleUserBlock'       => [AdminController::class, 'toggleUserBlock'],
    'getDashboardStats'     => [AdminController::class, 'getDashboardStats'],
];

if (isset($routes[$accion])) {
    list($controllerClass, $methodName) = $routes[$accion];
    
    $controller = match($controllerClass) {
        AuthController::class     => new AuthController($userService),
        ClientController::class   => new ClientController($transactionService, $pricingService, $beneficiaryService, $userService),
        AdminController::class    => new AdminController($transactionService, $pricingService, $userService, $dashboardService),
        DashboardController::class => new DashboardController($dashboardService),
        default => null
    };

    if ($controller) {
        $controller->$methodName();
    }
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Acción no válida o no encontrada.']);
}

?>