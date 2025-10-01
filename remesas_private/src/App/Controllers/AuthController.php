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

        $_SESSION['user_id'] = $result['UserID'];
        $_SESSION['user_name'] = $result['PrimerNombre'];
        $_SESSION['user_rol'] = $result['Rol'];
        $_SESSION['verification_status'] = $result['VerificacionEstado'];
        
        $this->sendJsonResponse([
            'success' => true, 
            'redirect' => BASE_URL . '/dashboard/',
            'verificationStatus' => $result['VerificacionEstado']
        ]);
    }

    public function registerUser(): void
    {
        $result = $this->userService->registerUser($_POST); 

        $_SESSION['user_id'] = $result['UserID'];
        $_SESSION['user_name'] = $result['PrimerNombre'];
        $_SESSION['user_rol'] = $result['Rol'];
        $_SESSION['verification_status'] = $result['VerificacionEstado'];

        $this->sendJsonResponse(['success' => true, 'redirect' => BASE_URL . '/dashboard/'], 201);
    }

    public function requestPasswordReset(): void
    {
        $data = $this->getJsonInput();
        $this->userService->requestPasswordReset($data['email'] ?? '');
        $this->sendJsonResponse(['success' => true, 'message' => 'Si tu correo está en nuestro sistema, recibirás un enlace.']);
    }

    public function performPasswordReset(): void
    {
        $data = $this->getJsonInput();
        $this->userService->performPasswordReset($data['token'] ?? '', $data['newPassword'] ?? '');
        $this->sendJsonResponse(['success' => true, 'message' => '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.']);
    }
}