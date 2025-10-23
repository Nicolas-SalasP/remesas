<?php
// remesas_private/src/App/Services/UserService.php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\EstadoVerificacionRepository;
use App\Repositories\RolRepository;
use App\Repositories\TipoDocumentoRepository;
use App\Services\NotificationService;
use App\Services\FileHandlerService;
use Exception;
use PragmaRX\Google2FA\Google2FA;

class UserService
{
    private UserRepository $userRepository;
    private NotificationService $notificationService;
    private FileHandlerService $fileHandler;
    private EstadoVerificacionRepository $estadoVerificacionRepo;
    private RolRepository $rolRepo;
    private TipoDocumentoRepository $tipoDocumentoRepo;

    private const ENCRYPTION_KEY = 'tu_clave_secreta_aqui_muy_segura'; // ¡MOVER A config.php!
    private const ENCRYPTION_CIPHER = 'aes-256-cbc';

    public function __construct(
        UserRepository $userRepository,
        NotificationService $notificationService,
        FileHandlerService $fileHandler,
        EstadoVerificacionRepository $estadoVerificacionRepo,
        RolRepository $rolRepo,
        TipoDocumentoRepository $tipoDocumentoRepo
    ) {
        $this->userRepository = $userRepository;
        $this->notificationService = $notificationService;
        $this->fileHandler = $fileHandler;
        $this->estadoVerificacionRepo = $estadoVerificacionRepo;
        $this->rolRepo = $rolRepo;
        $this->tipoDocumentoRepo = $tipoDocumentoRepo;
    }

    public function loginUser(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user['PasswordHash'])) {
            if ($user) {
                // Lógica de incremento de intentos y bloqueo
            }
            throw new Exception("Correo electrónico o contraseña no válidos.", 401);
        }
        if ($user['LockoutUntil'] && strtotime($user['LockoutUntil']) > time()) {
            throw new Exception("La cuenta está bloqueada temporalmente. Inténtalo más tarde.", 403);
        }

        $this->userRepository->updateLoginAttempts($user['UserID'], 0, null);
        $this->notificationService->logAdminAction($user['UserID'], 'Inicio de Sesión Exitoso', '');

        return $user;
    }

    public function registerUser(array $data): array
    {
        $requiredFields = ['primerNombre', 'primerApellido', 'email', 'password', 'tipoDocumento', 'numeroDocumento'];
        foreach($requiredFields as $field) {
            if (empty($data[$field])) {
                 throw new Exception("El campo '$field' es obligatorio.", 400);
            }
        }
        if (strlen($data['password']) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres.", 400);
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
             throw new Exception("El formato del correo electrónico no es válido.", 400);
        }

        $data['passwordHash'] = password_hash($data['password'], PASSWORD_DEFAULT);

        $tipoDocumentoID = $this->tipoDocumentoRepo->findIdByName($data['tipoDocumento']);
        if (!$tipoDocumentoID) {
            throw new Exception("Tipo de documento '{$data['tipoDocumento']}' no válido.", 400);
        }
        $data['tipoDocumentoID'] = $tipoDocumentoID;

        $rolPersonaNaturalID = $this->rolRepo->findIdByName('Persona Natural');
         if (!$rolPersonaNaturalID) throw new Exception("Rol 'Persona Natural' no encontrado en la base de datos.", 500);
        $data['rolID'] = $rolPersonaNaturalID;

        try {
            $userId = $this->userRepository->create($data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode() ?: 500);
        }

        $this->notificationService->logAdminAction($userId, 'Registro de Usuario', "Email: " . $data['email']);

        $newUser = $this->userRepository->findByEmail($data['email']);
        if (!$newUser) throw new Exception("Error al obtener datos del usuario recién registrado.", 500);
        return $newUser;
    }

    public function requestPasswordReset(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);

            if ($this->userRepository->createResetToken($user['UserID'], $token, $expires)) {
                $this->notificationService->sendPasswordResetEmail($email, $token);
                $this->notificationService->logAdminAction($user['UserID'], 'Solicitud Recuperación Contraseña', "Token generado para {$email}");
            } else {
                 $this->notificationService->logAdminAction($user['UserID'], 'Error Recuperación Contraseña', "Fallo al crear token para {$email}");
            }
        } else {
             $this->notificationService->logAdminAction(null, 'Intento Recuperación Contraseña Fallido', "Email no encontrado: {$email}");
        }
    }

    public function performPasswordReset(string $token, string $newPassword): void
    {
        if (strlen($newPassword) < 6) {
            throw new Exception("La contraseña debe tener al menos 6 caracteres.", 400);
        }

        $resetData = $this->userRepository->findValidResetToken($token);
        if (!$resetData) {
            throw new Exception("Token no válido o expirado. Solicita uno nuevo.", 400);
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($this->userRepository->updatePassword($resetData['UserID'], $passwordHash)) {
            $this->userRepository->markTokenAsUsed($resetData['ResetID']);
            $this->notificationService->logAdminAction($resetData['UserID'], 'Contraseña Restablecida', "Contraseña actualizada mediante token.");
        } else {
            $this->notificationService->logAdminAction($resetData['UserID'], 'Error Restablecimiento Contraseña', "Fallo al actualizar contraseña con token.");
            throw new Exception("No se pudo actualizar la contraseña.", 500);
        }
    }

    public function getUserProfile(int $userId): array
    {
        $profile = $this->userRepository->findUserById($userId);
        if (!$profile) {
            throw new Exception("Perfil de usuario no encontrado.", 404);
        }
        return $profile;
    }

    public function uploadVerificationDocs(int $userId, array $files): void
    {
        if (!isset($files['docFrente']) || !isset($files['docReverso'])) {
            throw new Exception("Debes subir ambos lados del documento.", 400);
        }

        $estadoPendienteID = $this->estadoVerificacionRepo->findIdByName('Pendiente');
        if (!$estadoPendienteID) throw new Exception("Estado 'Pendiente' no encontrado en la base de datos.", 500);

        try {
            $pathFrente = $this->fileHandler->saveVerificationFile($files['docFrente'], $userId, 'frente');
            $pathReverso = $this->fileHandler->saveVerificationFile($files['docReverso'], $userId, 'reverso');
        } catch (Exception $e) {
             throw new Exception("Error al guardar archivos: " . $e->getMessage(), $e->getCode() ?: 500);
        }


        if ($this->userRepository->updateVerificationDocuments($userId, $pathFrente, $pathReverso, $estadoPendienteID)) {
            $this->notificationService->logAdminAction($userId, 'Subida Documentos Verificación', "Usuario ID: $userId. Estado cambiado a Pendiente.");
        } else {
            @unlink(__DIR__ . '/../../../' . $pathFrente);
            @unlink(__DIR__ . '/../../../' . $pathReverso);
            throw new Exception("No se pudieron actualizar los datos de verificación en la base de datos.", 500);
        }
    }

    public function updateVerificationStatus(int $adminId, int $userId, string $newStatusName): void
    {
       if (!in_array($newStatusName, ['Verificado', 'Rechazado'])) {
           throw new Exception("Estado de verificación no válido para esta acción: '{$newStatusName}'.", 400);
       }

       $newStatusID = $this->estadoVerificacionRepo->findIdByName($newStatusName);
       if (!$newStatusID) {
            throw new Exception("Estado '{$newStatusName}' no encontrado.", 500);
       }

        $estadoPendienteID = $this->estadoVerificacionRepo->findIdByName('Pendiente');
       if (!$estadoPendienteID) throw new Exception("Estado 'Pendiente' no encontrado.", 500);

       $user = $this->userRepository->findUserById($userId);
       if (!$user) {
           throw new Exception("Usuario no encontrado.", 404);
       }
       if ($user['VerificacionEstadoID'] !== $estadoPendienteID) {
            throw new Exception("Solo se puede aprobar o rechazar un usuario en estado 'Pendiente'. Estado actual: {$user['VerificacionEstado']}", 409);
       }


       if ($this->userRepository->updateVerificationStatus($userId, $newStatusID)) {
            $this->notificationService->logAdminAction($adminId, 'Admin actualizó estado verificación', "Usuario ID: $userId, Nuevo Estado: $newStatusName (ID: $newStatusID)");
       } else {
           throw new Exception("No se pudo actualizar el estado de verificación.", 500);
       }
    }

    public function toggleUserBlock(int $adminId, int $userId, string $newStatus): void
    {
        if (!in_array($newStatus, ['blocked', 'active'])) {
             throw new Exception("Acción de bloqueo no válida: '{$newStatus}'.", 400);
        }

        $lockoutUntil = ($newStatus === 'blocked')
            ? date('Y-m-d H:i:s', strtotime('+10 years'))
            : null;

        if ($this->userRepository->updateLoginAttempts($userId, 0, $lockoutUntil)) {
            $actionText = $newStatus === 'blocked' ? 'Bloqueado' : 'Desbloqueado';
            $this->notificationService->logAdminAction($adminId, "Admin cambió estado de usuario", "Usuario ID: $userId, Nuevo Estado: $actionText");
        } else {
             throw new Exception("No se pudo actualizar el estado de bloqueo del usuario.", 500);
        }
    }

    private function encryptData(string $data): string {
        $ivLength = openssl_cipher_iv_length(self::ENCRYPTION_CIPHER);
        if ($ivLength === false) throw new Exception("Cipher inválido para encriptación.");
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($data, self::ENCRYPTION_CIPHER, self::ENCRYPTION_KEY, 0, $iv);
        if ($encrypted === false) throw new Exception("Fallo en encriptación.");
        return base64_encode($iv . $encrypted);
    }

    private function decryptData(string $data): ?string {
        $decoded = base64_decode($data);
        if ($decoded === false) return null;
        $ivLength = openssl_cipher_iv_length(self::ENCRYPTION_CIPHER);
        if ($ivLength === false) return null;
        if (strlen($decoded) < $ivLength) return null;
        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);
        $decrypted = openssl_decrypt($encrypted, self::ENCRYPTION_CIPHER, self::ENCRYPTION_KEY, 0, $iv);
        return $decrypted === false ? null : $decrypted;
    }

     private function generateBackupCodes(int $count = 10, int $length = 8): array {
        $codes = [];
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charLength = strlen($characters);
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < $length; $j++) {
                $code .= $characters[random_int(0, $charLength - 1)];
            }
            $codes[] = $code;
        }
        return $codes;
     }

     public function generateUser2FASecret(int $userId, string $userEmail, string $appName = 'JC Envíos'): array {
        $google2fa = new Google2FA();
        $secretKey = $google2fa->generateSecretKey();
        $encryptedSecret = $this->encryptData($secretKey);
        $this->userRepository->update2FASecret($userId, $encryptedSecret);
        $qrCodeUrl = $google2fa->getQRCodeUrl($appName, $userEmail, $secretKey);
        return ['secret' => $secretKey, 'qrCodeUrl' => $qrCodeUrl];
     }

     public function verifyAndEnable2FA(int $userId, string $userProvidedCode): bool {
        $encryptedSecret = $this->userRepository->get2FASecret($userId);
        if (!$encryptedSecret) return false;
        $secretKey = $this->decryptData($encryptedSecret);
        if (!$secretKey) return false;
        $google2fa = new Google2FA();
        $isValid = $google2fa->verifyKey($secretKey, $userProvidedCode);
        if ($isValid) {
            $backupCodes = $this->generateBackupCodes();
            $encryptedBackupCodes = $this->encryptData(json_encode($backupCodes));
            if ($this->userRepository->enable2FA($userId, $encryptedBackupCodes)) {
                 $_SESSION['show_backup_codes'] = $backupCodes;
                 return true;
            } else {
                 return false;
            }
        }
        return false;
     }

     public function verifyUser2FACode(int $userId, string $code): bool {
        $encryptedSecret = $this->userRepository->get2FASecret($userId);
        if (!$encryptedSecret) return false;
        $secretKey = $this->decryptData($encryptedSecret);
        if (!$secretKey) return false;
        $google2fa = new Google2FA();
        return $google2fa->verifyKey($secretKey, $code);
     }

     public function verifyBackupCode(int $userId, string $code): bool {
        $encryptedBackupCodes = $this->userRepository->getBackupCodes($userId);
        if (!$encryptedBackupCodes) return false;
        $backupCodesJson = $this->decryptData($encryptedBackupCodes);
        if (!$backupCodesJson) return false;
        $backupCodes = json_decode($backupCodesJson, true);
        if (!is_array($backupCodes)) return false;

        $key = array_search($code, $backupCodes);
        if ($key !== false) {
            unset($backupCodes[$key]);
            $newEncryptedBackupCodes = $this->encryptData(json_encode(array_values($backupCodes))); // Re-indexar
            $this->userRepository->updateBackupCodes($userId, $newEncryptedBackupCodes);
            $this->notificationService->logAdminAction($userId, 'Código Respaldo 2FA Usado', "Se utilizó un código de respaldo.");
            return true;
        }
        return false;
     }
}