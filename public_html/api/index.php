<?php
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

use App\Database\Database;
use App\Repositories\{UserRepository, RateRepository, CountryRepository, BeneficiaryRepository, TransactionRepository};
use App\Services\{LogService, NotificationService, PDFService, FileHandlerService, UserService, PricingService, TransactionService, BeneficiaryService, DashboardService};
use App\Controllers\{AuthController, ClientController, AdminController, DashboardController};

header('Content-Type: application/json');

class Container {
    private array $instances = [];
    private ?Database $db = null;

    public function getDb(): Database {
        if ($this->db === null) {
            $this->db = Database::getInstance();
        }
        return $this->db;
    }

    public function get(string $className) {
        if (!isset($this->instances[$className])) {
            $this->instances[$className] = $this->createInstance($className);
        }
        return $this->instances[$className];
    }

    private function createInstance(string $className) {
        return match ($className) {
            // Repositorios
            UserRepository::class => new UserRepository($this->getDb()),
            RateRepository::class => new RateRepository($this->getDb()),
            CountryRepository::class => new CountryRepository($this->getDb()),
            BeneficiaryRepository::class => new BeneficiaryRepository($this->getDb()),
            TransactionRepository::class => new TransactionRepository($this->getDb()),

            // Servicios
            LogService::class => new LogService($this->getDb()),
            NotificationService::class => new NotificationService($this->get(LogService::class)),
            PDFService::class => new PDFService(),
            FileHandlerService::class => new FileHandlerService(), 
            UserService::class => new UserService($this->get(UserRepository::class), $this->get(NotificationService::class), $this->get(FileHandlerService::class)),
            PricingService::class => new PricingService($this->get(RateRepository::class), $this->get(CountryRepository::class), $this->get(NotificationService::class)),
            BeneficiaryService::class => new BeneficiaryService($this->get(BeneficiaryRepository::class), $this->get(NotificationService::class)),
            TransactionService::class => new TransactionService($this->get(TransactionRepository::class), $this->get(UserRepository::class), $this->get(NotificationService::class), $this->get(PDFService::class), $this->get(FileHandlerService::class)),
            DashboardService::class => new DashboardService($this->get(TransactionRepository::class), $this->get(UserRepository::class), $this->get(RateRepository::class)),

            // Controladores
            AuthController::class => new AuthController($this->get(UserService::class)),
            ClientController::class => new ClientController($this->get(TransactionService::class), $this->get(PricingService::class), $this->get(BeneficiaryService::class), $this->get(UserService::class)),
            AdminController::class => new AdminController($this->get(TransactionService::class), $this->get(PricingService::class), $this->get(UserService::class), $this->get(DashboardService::class)),
            DashboardController::class => new DashboardController($this->get(DashboardService::class)),
            default => throw new Exception("Clase no configurada en el contenedor: {$className}")
        };
    }
}

try {
    $container = new Container();
    $accion = $_GET['accion'] ?? '';
    $requestMethod = $_SERVER['REQUEST_METHOD']; 

    $routes = [
        // Rutas Públicas
        'loginUser'             => [AuthController::class, 'loginUser', 'POST'],
        'registerUser'          => [AuthController::class, 'registerUser', 'POST'],
        'requestPasswordReset'  => [AuthController::class, 'requestPasswordReset', 'POST'],
        'performPasswordReset'  => [AuthController::class, 'performPasswordReset', 'POST'],
        'getTasa'               => [ClientController::class, 'getTasa', 'GET'],
        'getPaises'             => [ClientController::class, 'getPaises', 'GET'],
        'getDolarBcv'           => [DashboardController::class, 'getDolarBcvData', 'GET'],

        // Rutas de Cliente (
        'getCuentas'            => [ClientController::class, 'getCuentas', 'GET'],
        'addCuenta'             => [ClientController::class, 'addCuenta', 'POST'],
        'createTransaccion'     => [ClientController::class, 'createTransaccion', 'POST'],
        'cancelTransaction'     => [ClientController::class, 'cancelTransaction', 'POST'], 
        'uploadReceipt'         => [ClientController::class, 'uploadReceipt', 'POST'],
        'getUserProfile'        => [ClientController::class, 'getUserProfile', 'GET'],
        'uploadVerificationDocs'=> [ClientController::class, 'uploadVerificationDocs', 'POST'],

        // Rutas de Admin 
        'updateRate'            => [AdminController::class, 'updateRate', 'POST'], 
        'addPais'               => [AdminController::class, 'addPais', 'POST'],
        'updatePaisRol'         => [AdminController::class, 'updatePaisRol', 'POST'], 
        'togglePaisStatus'      => [AdminController::class, 'togglePaisStatus', 'POST'], 
        'processTransaction'    => [AdminController::class, 'processTransaction', 'POST'],
        'rejectTransaction'     => [AdminController::class, 'rejectTransaction', 'POST'], 
        'adminUploadProof'      => [AdminController::class, 'adminUploadProof', 'POST'],
        'updateVerificationStatus' => [AdminController::class, 'updateVerificationStatus', 'POST'],
        'toggleUserBlock'       => [AdminController::class, 'toggleUserBlock', 'POST'], 
        'getDashboardStats'     => [AdminController::class, 'getDashboardStats', 'GET'],
    ];

    if (isset($routes[$accion])) {
        list($controllerClass, $methodName, $expectedMethod) = $routes[$accion];

        if ($requestMethod !== $expectedMethod) {
             http_response_code(405); 
             echo json_encode(['success' => false, 'error' => 'Método HTTP no permitido para esta acción.']);
            exit();
        }

        $controller = $container->get($controllerClass);

        if (method_exists($controller, $methodName)) {
            $controller->$methodName(); 
        } else {
            throw new Exception("Método '{$methodName}' no encontrado en el controlador '{$controllerClass}'.", 500);
        }

    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Acción no válida o no encontrada.']);
    }

} catch (\Throwable $e) {
    \App\Core\exception_handler($e);
}

?>