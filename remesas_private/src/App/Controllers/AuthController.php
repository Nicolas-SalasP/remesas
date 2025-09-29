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

            $_SESSION['user_id'] = $result['UserID'];
            $_SESSION['user_name'] = $result['PrimerNombre'];
            $_SESSION['user_rol'] = $result['Rol'];
            $_SESSION['verification_status'] = $result['VerificacionEstado'];
            
            $this->sendJsonResponse([
                'success' => true, 
                'redirect' => BASE_URL . '/dashboard/',
                'verificationStatus' => $result['VerificacionEstado']
            ]);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 401);
        }
    }

    public function registerUser(): void
    {
        try {
            $result = $this->userService->registerUser($_POST); 

            $_SESSION['user_id'] = $result['UserID'];
            $_SESSION['user_name'] = $result['PrimerNombre'];
            $_SESSION['user_rol'] = $result['Rol'];
            $_SESSION['verification_status'] = $result['VerificacionEstado'];

            $this->sendJsonResponse(['success' => true, 'redirect' => BASE_URL . '/dashboard/'], 201);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function requestPasswordReset(): void
    {
        try {
            $data = $this->getJsonInput();
            $this->userService->requestPasswordReset($data['email'] ?? '');
            $this->sendJsonResponse(['success' => true, 'message' => 'Si tu correo está en nuestro sistema, recibirás un enlace.']);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function performPasswordReset(): void
    {
        try {
            $data = $this->getJsonInput();
            $this->userService->performPasswordReset($data['token'] ?? '', $data['newPassword'] ?? '');
            $this->sendJsonResponse(['success' => true, 'message' => '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.']);
        } catch (Exception $e) {
            $this->sendJsonResponse(['success' => false, 'error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}