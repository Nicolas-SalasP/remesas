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
        try {
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
            $_SESSION['user_photo_url'] = $result['FotoPerfilURL'] ?? null;
            $_SESSION['ultima_actividad'] = time();

            $redirectUrl = BASE_URL . '/dashboard/';
            $userRol = $result['Rol'];

            if ($userRol === 'Admin' || $userRol === 'Operador') {
                $redirectUrl = BASE_URL . '/dashboard/seguridad.php';
            } elseif ($result['VerificacionEstado'] !== 'Verificado') {
                $redirectUrl = BASE_URL . '/dashboard/verificar.php';
            }
            
            $this->sendJsonResponse([
                'success' => true,
                'twofa_required' => false,
                'redirect' => $redirectUrl,
                'verificationStatus' => $result['VerificacionEstado']
            ]);

        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 ? $e->getCode() : 401;
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $statusCode);
        }
    }

    public function registerUser(): void
    {
        $data = $_POST;

        try {
            $result = $this->userService->registerUser($data);

            $_SESSION['user_id'] = $result['UserID'];
            $_SESSION['user_name'] = $result['PrimerNombre'];
            $_SESSION['user_rol_name'] = $result['Rol'];
            $_SESSION['verification_status'] = $result['VerificacionEstado'];
            $_SESSION['twofa_enabled'] = $result['twofa_enabled'];
            $_SESSION['user_photo_url'] = $result['FotoPerfilURL'] ?? null;
            $_SESSION['ultima_actividad'] = time();

            $redirectUrl = BASE_URL . '/dashboard/verificar.php';

            $this->sendJsonResponse(['success' => true, 'redirect' => $redirectUrl], 201);

        } catch (Exception $e) {
            $statusCode = $e->getCode() >= 400 ? $e->getCode() : 400; 
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $statusCode);
        }
    }

    public function requestPasswordReset(): void
    {
        try {
            $data = $this->getJsonInput();
            $this->userService->requestPasswordReset($data['email'] ?? '');
            $this->sendJsonResponse(['success' => true, 'message' => 'Si tu correo está en nuestro sistema, recibirás un enlace para restablecer tu contraseña.']);
        
        } catch (Exception $e) {
            $this->sendJsonResponse([
                'success' => false, 
                'error' => 'No se pudo enviar el correo de recuperación en este momento. Por favor, contacta a soporte. (Error: ' . $e->getMessage() . ')'
            ], 500);
        }
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
        $code = trim($data['code'] ?? '');
        $isValid = false;

        if (!empty($code)) {
            try {
                $isValid = $this->userService->verifyUser2FACode($userId, $code);
            } catch (Exception $e) {
                $isValid = false;
            }

            if (!$isValid) {
                $isValid = $this->userService->verifyBackupCode($userId, $code);
            }
        }

        if ($isValid) {
            unset($_SESSION['2fa_user_id']);

            $_SESSION['2fa_verified_at'] = time();
            $user = $this->userService->getUserProfile($userId);

            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['user_name'] = $user['PrimerNombre'];
            $_SESSION['user_rol_name'] = $user['Rol'];
            $_SESSION['verification_status'] = $user['VerificacionEstado'];
            $_SESSION['twofa_enabled'] = $user['twofa_enabled'];
            $_SESSION['user_photo_url'] = $user['FotoPerfilURL'] ?? null;
            $_SESSION['ultima_actividad'] = time();

            session_regenerate_id(true); 

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