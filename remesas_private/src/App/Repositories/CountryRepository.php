<?php
namespace App\Repositories;

use App\Database\Database;
use Exception;

class CountryRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findNameById(int $paisId): ?string
    {
        $sql = "SELECT NombrePais FROM paises WHERE PaisID = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $paisId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['NombrePais'] ?? null;
    }

    public function findIdByName(string $nombrePais): ?int
    {
        $sql = "SELECT PaisID FROM paises WHERE NombrePais = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $nombrePais);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['PaisID'] ?? null;
    }
    
    public function findMonedaById(int $paisId): ?string
    {
        $sql = "SELECT CodigoMoneda FROM paises WHERE PaisID = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $paisId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['CodigoMoneda'] ?? null;
    }

    public function findByRoleAndStatus(string $rol, bool $activo = true): array
    {
        $sql = "SELECT PaisID, NombrePais, CodigoMoneda FROM paises 
                WHERE (Rol = ? OR Rol = 'Ambos') AND Activo = ?";
        
        $stmt = $this->db->prepare($sql);
        $activoInt = (int) $activo;
        $stmt->bind_param("si", $rol, $activoInt);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stmt->close();
        
        return $result;
    }

    public function create(string $nombrePais, string $codigoMoneda, string $rol): int
    {
        $sql = "INSERT INTO paises (NombrePais, CodigoMoneda, Rol) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sss", $nombrePais, $codigoMoneda, $rol);
        
        if (!$stmt->execute()) {
             throw new Exception("Error al crear país. Podría ser un nombre duplicado: " . $stmt->error);
        }
        $newId = $stmt->insert_id;
        $stmt->close();
        return $newId;
    }

    public function update(int $paisId, string $nombrePais, string $codigoMoneda): bool
    {
        $sql = "UPDATE paises SET NombrePais = ?, CodigoMoneda = ? WHERE PaisID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi", $nombrePais, $codigoMoneda, $paisId);
        
        if (!$stmt->execute()) {
             throw new Exception("Error al actualizar el país. Podría ser un nombre duplicado: " . $stmt->error);
        }
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }

    public function updateRole(int $paisId, string $newRole): bool
    {
        $sql = "UPDATE paises SET Rol = ? WHERE PaisID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $newRole, $paisId);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }

    public function updateStatus(int $paisId, bool $newStatus): bool
    {
        $sql = "UPDATE paises SET Activo = ? WHERE PaisID = ?";
        $stmt = $this->db->prepare($sql);
        $newStatusInt = (int) $newStatus;
        $stmt->bind_param("ii", $newStatusInt, $paisId);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }
}