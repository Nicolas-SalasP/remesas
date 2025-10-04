<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Exception;

class UserService
{
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private FileHandlerService $fileHandler;

    public function __construct(
        UserRepository $userRepository,
        NotificationService $notificationService,
        FileHandlerService $fileHandler
    ) {
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
        $this->fileHandler = $fileHandler;
    }

    public function loginUser(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user['PasswordHash'])) {
            if ($user) {
            }
            throw new Exception("Correo electrónico o contraseña no válidos.", 401);
        }

        if ($user['LockoutUntil'] && strtotime($user['LockoutUntil']) > time()) {
            throw new Exception("La cuenta está bloqueada. Inténtalo más tarde.", 403);
        }

        $this->userRepository->updateLoginAttempts($user['UserID'], 0, null);
        $this->notificationService->logAdminAction($user['UserID'], 'Inicio de Sesión Exitoso', '');
        return $user;
    }

    public function registerUser(array $data): array
    {
        if (empty($data['primerNombre']) || empty($data['primerApellido']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception("Todos los campos marcados con * son obligatorios.", 400);
        }
        if (strlen($data['password']) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres.", 400);
        }
        if ($this->userRepository->findByEmail($data['email'])) {
            throw new Exception("El correo electrónico ya está registrado.", 409);
        }
        
        $data['passwordHash'] = password_hash($data['password'], PASSWORD_DEFAULT);

        $userId = $this->userRepository->create($data);

        $this->notificationService->logAdminAction($userId, 'Registro de Usuario', "Email: " . $data['email']);
        
        return $this->userRepository->findByEmail($data['email']);
    }

    public function requestPasswordReset(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); 
            
            if ($this->userRepository->createResetToken($user['UserID'], $token, $expires)) {
                $this->notificationService->sendPasswordResetEmail($email, $token);
            }
        }
    }

    public function performPasswordReset(string $token, string $newPassword): void
    {
        if (strlen($newPassword) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres.", 400);
        }

        $resetData = $this->userRepository->findValidResetToken($token);
        if (!$resetData) {
            throw new Exception("Token no válido o expirado.", 400);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($this->userRepository->updatePassword($resetData['UserID'], $passwordHash)) {
            $this->userRepository->markTokenAsUsed($resetData['ResetID']);
        }
    }

    public function getUserProfile(int $userId): array
    {
        return $this->userRepository->findUserById($userId) ?? [];
    }

    public function uploadVerificationDocs(int $userId, array $files): void
    {
        if (!isset($files['docFrente']) || !isset($files['docReverso'])) {
            throw new Exception("Debes subir ambos lados del documento.", 400);
        }

        $pathFrente = $this->fileHandler->saveVerificationFile($files['docFrente'], $userId, 'frente');
        $pathReverso = $this->fileHandler->saveVerificationFile($files['docReverso'], $userId, 'reverso');

        if ($this->userRepository->updateVerificationDocuments($userId, $pathFrente, $pathReverso)) {
            $this->notificationService->logAdminAction($userId, 'Subida de Documentos de Verificación', "Usuario ID: $userId");
        } else {
            @unlink($pathFrente);
            @unlink($pathReverso);
            throw new Exception("No se pudieron guardar los documentos en la base de datos.", 500);
        }
    }
    
    public function updateVerificationStatus(int $adminId, int $userId, string $newStatus): void
    {
       if(!in_array($newStatus, ['Verificado', 'Rechazado'])) {
           throw new Exception("Estado de verificación no válido", 400);
       }
       $this->userRepository->updateVerificationStatus($userId, $newStatus);
       $this->notificationService->logAdminAction($adminId, 'Admin actualizó estado de verificación', "Usuario ID: $userId, Nuevo Estado: $newStatus");
    }

    public function toggleUserBlock(int $adminId, int $userId, string $newStatus): void
    {
        $lockoutUntil = ($newStatus === 'blocked') ? date('Y-m-d H:i:s', time() + (10 * 365 * 24 * 60 * 60)) : null; // Bloqueo "permanente"
        $this->userRepository->updateLoginAttempts($userId, 0, $lockoutUntil);
        $actionText = $newStatus === 'blocked' ? 'Bloqueado' : 'Desbloqueado';
        $this->notificationService->logAdminAction($adminId, "Admin cambió estado de usuario", "Usuario ID: $userId, Nuevo Estado: $actionText");
    }
}
