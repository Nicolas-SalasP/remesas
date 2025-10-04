<?php

require_once __DIR__ . '/../../remesas_private/src/core/init.php';

// Importación de Clases
use App\Database\Database;
use App\Repositories\UserRepository;
use App\Repositories\RateRepository;
use App\Repositories\CountryRepository;
use App\Repositories\BeneficiaryRepository;
use App\Repositories\TransactionRepository;
use App\Services\LogService;
use App\Services\NotificationService;
use App\Services\PDFService;
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

    // Repositorios
    $userRepository = new UserRepository($db);
    $rateRepository = new RateRepository($db);
    $countryRepository = new CountryRepository($db);
    $beneficiaryRepository = new BeneficiaryRepository($db);
    $transactionRepository = new TransactionRepository($db);
    
    // Servicios de Utilidad
    $logService = new LogService($db); 
    $notificationService = new NotificationService($logService); 
    $pdfService = new PDFService();
    
    // Servicios de Negocio 
    $userService = new UserService($userRepository, $notificationService);
    $pricingService = new PricingService($rateRepository, $countryRepository, $notificationService);
    $beneficiaryService = new BeneficiaryService($beneficiaryRepository, $notificationService);
    $transactionService = new TransactionService($transactionRepository, $userRepository, $notificationService, $pdfService);
    // Corregido: DashboardService espera un RateRepository, no la conexión a la BD.
    $dashboardService = new DashboardService($rateRepository); 

} catch (Exception $e) {
    error_log("Error de Inicialización DB: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de inicialización del sistema.']);
    exit();
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
    'getDolarBcv'           => [DashboardController::class, 'getDolarBcvData'], // Ruta para el gráfico

    // Rutas de Cliente (Logueado)
    'getCuentas'            => [ClientController::class, 'getCuentas'],
    'addCuenta'             => [ClientController::class, 'addCuenta'],
    'createTransaccion'     => [ClientController::class, 'createTransaccion'],
    'cancelTransaction'     => [ClientController::class, 'cancelTransaction'],
    'uploadReceipt'         => [ClientController::class, 'uploadReceipt'],
    'getUserProfile'        => [ClientController::class, 'getUserProfile'],
    'uploadVerificationDocs'=> [ClientController::class, 'uploadVerificationDocs'],
    
    // Rutas de Administración (Admin)
    'updateRate'            => [AdminController::class, 'updateRate'],
    'addPais'               => [AdminController::class, 'addPais'],
    'updatePaisRol'         => [AdminController::class, 'updatePaisRol'],
    'processTransaction'    => [AdminController::class, 'processTransaction'],
    'adminUploadProof'      => [AdminController::class, 'adminUploadProof'],
    'updateVerificationStatus'=> [AdminController::class, 'updateVerificationStatus'],
    'getDashboardStats'     => [DashboardController::class, 'getStats'],
];

if (isset($routes[$accion])) {
    list($controllerClass, $methodName) = $routes[$accion];
    
    $controller = match($controllerClass) {
        AuthController::class     => new AuthController($userService),
        ClientController::class   => new ClientController($transactionService, $pricingService, $beneficiaryService, $userService),
        AdminController::class    => new AdminController($transactionService, $pricingService, $userService),
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