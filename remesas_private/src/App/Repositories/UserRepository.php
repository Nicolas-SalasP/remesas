<?php

namespace App\Repositories;

use App\Database\Database;
use Exception;

class UserRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT UserID, PasswordHash, PrimerNombre, Rol, VerificacionEstado, FailedLoginAttempts, LockoutUntil 
                FROM usuarios WHERE Email = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO usuarios (PrimerNombre, SegundoNombre, PrimerApellido, SegundoApellido, Email, PasswordHash, TipoDocumento, NumeroDocumento, VerificacionEstado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'No Verificado')";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bind_param("ssssssss", 
            $data['primerNombre'], 
            $data['segundoNombre'], 
            $data['primerApellido'], 
            $data['segundoApellido'], 
            $data['email'], 
            $data['passwordHash'], 
            $data['tipoDocumento'], 
            $data['numeroDocumento']
        );

        if (!$stmt->execute()) {
             throw new Exception("Error al insertar usuario: " . $stmt->error);
        }
        $newId = $stmt->insert_id;
        $stmt->close();
        return $newId;
    }

    public function findUserById(int $userId): ?array
    {
        $sql = "SELECT UserID, PrimerNombre, PrimerApellido, Email, Telefono, TipoDocumento, NumeroDocumento, VerificacionEstado 
                FROM usuarios WHERE UserID = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }

    public function updateLoginAttempts(int $userId, int $attempts, ?string $lockoutUntil): bool
    {
        $sql = "UPDATE usuarios SET FailedLoginAttempts = ?, LockoutUntil = ? WHERE UserID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isi", $attempts, $lockoutUntil, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function createResetToken(int $userId, string $token, string $expiresAt): bool
    {
        $sql = "INSERT INTO PasswordResets (UserID, Token, ExpiresAt) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $userId, $token, $expiresAt);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function findValidResetToken(string $token): ?array
    {
        $sql = "SELECT * FROM PasswordResets WHERE Token = ? AND Used = FALSE AND ExpiresAt > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $sql = "UPDATE usuarios SET PasswordHash = ? WHERE UserID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $passwordHash, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function markTokenAsUsed(int $resetId): bool
    {
        $sql = "UPDATE PasswordResets SET Used = TRUE WHERE ResetID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $resetId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function updateVerificationDocuments(int $userId, string $pathFrente, string $pathReverso): bool
    {
        $sql = "UPDATE usuarios SET DocumentoImagenURL_Frente = ?, DocumentoImagenURL_Reverso = ?, VerificacionEstado = 'Pendiente' WHERE UserID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi", $pathFrente, $pathReverso, $userId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    
    // --- MÉTODO PARA ESTADÍSTICAS ---

    public function countAll(): int
    {
        $sql = "SELECT COUNT(UserID) as total FROM usuarios WHERE Rol = 'Usuario'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($result['total'] ?? 0);
    }
}