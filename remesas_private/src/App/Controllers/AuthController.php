<?php
namespace App\Controllers;

use App\Services\UserService;
use Exception;

class AuthController extends BaseController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function loginUser(): void
    {
        $data = $this->getJsonInput();
        $result = $this->userService->loginUser($data['email'] ?? '', $data['password'] ?? '');

        if ($result['twofa_enabled']) {
             $_SESSION['2fa_user_id'] = $result['UserID'];
             unset($_SESSION['user_id']);
             unset($_SESSION['user_rol_name']);
             $this->sendJsonResponse([
                 'success' => true,
                 'twofa_required' => true,
                 'redirect' => BASE_URL . '/verify-2fa.php'
             ]);
             return;
        }
        
        $_SESSION['user_id'] = $result['UserID'];
        $_SESSION['user_name'] = $result['PrimerNombre'];
        $_SESSION['user_rol_name'] = $result['Rol'];
        $_SESSION['verification_status'] = $result['VerificacionEstado'];
        $_SESSION['twofa_enabled'] = $result['twofa_enabled'];
        $_SESSION['ultima_actividad'] = time();
        
        $this->sendJsonResponse([
            'success' => true,
            'twofa_required' => false,
            'redirect' => BASE_URL . '/dashboard/seguridad.php',
            'verificationStatus' => $result['VerificacionEstado']
        ]);
    }

    public function registerUser(): void
    {
        $data = $_POST;

        if (empty($data['primerNombre']) || empty($data['primerApellido']) || empty($data['email']) || empty($data['password']) || empty($data['tipoDocumento']) || empty($data['numeroDocumento']) || empty($data['telefono'])) {
             $this->sendJsonResponse(['success' => false, 'error' => 'Faltan campos obligatorios.'], 400);
             return;
        }

        $result = $this->userService->registerUser($data);

        $_SESSION['user_id'] = $result['UserID'];
        $_SESSION['user_name'] = $result['PrimerNombre'];
        $_SESSION['user_rol_name'] = $result['Rol'];
        $_SESSION['verification_status'] = $result['VerificacionEstado'];
        $_SESSION['twofa_enabled'] = $result['twofa_enabled'];
        $_SESSION['ultima_actividad'] = time();

        $redirectUrl = BASE_URL . '/dashboard/verificar.php';

        $this->sendJsonResponse(['success' => true, 'redirect' => $redirectUrl], 201);
    }

    public function requestPasswordReset(): void
    {
        $data = $this->getJsonInput();
        $this->userService->requestPasswordReset($data['email'] ?? '');
        $this->sendJsonResponse(['success' => true, 'message' => 'Si tu correo está en nuestro sistema, recibirás un enlace para restablecer tu contraseña.']);
    }

    public function performPasswordReset(): void
    {
        $data = $this->getJsonInput();
        $this->userService->performPasswordReset($data['token'] ?? '', $data['newPassword'] ?? '');
        $this->sendJsonResponse(['success' => true, 'message' => '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.']);
    }

     public function verify2FACode(): void
     {
         if (!isset($_SESSION['2fa_user_id'])) {
             $this->sendJsonResponse(['success' => false, 'error' => 'No hay una autenticación pendiente.'], 400);
             return;
         }
         
         $userId = $_SESSION['2fa_user_id'];
         $data = $this->getJsonInput();
         $code = $data['code'] ?? '';

         $isValid = false;
         if (!empty($code)) {
            $isValid = $this->userService->verifyUser2FACode($userId, $code);
             if (!$isValid) {
                 $isValid = $this->userService->verifyBackupCode($userId, $code);
             }
         }

         if ($isValid) {
             unset($_SESSION['2fa_user_id']);
             session_regenerate_id(true);
             
             $user = $this->userService->getUserProfile($userId); 

             $_SESSION['user_id'] = $user['UserID'];
             $_SESSION['user_name'] = $user['PrimerNombre'];
             $_SESSION['user_rol_name'] = $user['Rol'];
             $_SESSION['verification_status'] = $user['VerificacionEstado'];
             $_SESSION['twofa_enabled'] = $user['twofa_enabled']; // Será true
             $_SESSION['ultima_actividad'] = time();
             $redirectUrl = BASE_URL . '/dashboard/'; 
             
             if ($user['Rol'] === 'Admin') {
                 $redirectUrl = BASE_URL . '/admin/';
             } elseif ($user['Rol'] === 'Operador') {
                 $redirectUrl = BASE_URL . '/operador/pendientes.php';
             } elseif ($user['VerificacionEstado'] !== 'Verificado') {
                 $redirectUrl = BASE_URL . '/dashboard/verificar.php';
             }

             $this->sendJsonResponse(['success' => true, 'redirect' => $redirectUrl]);
         } else {
             $this->sendJsonResponse(['success' => false, 'error' => 'Código 2FA o de respaldo inválido.'], 401);
         }
     }
}