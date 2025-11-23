<?php
namespace App\Repositories;

use App\Database\Database;
use Exception;

class RateRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findCurrentRate(int $origenID, int $destinoID, float $montoOrigen = 0): ?array
    {
        $sql = "SELECT TasaID, ValorTasa 
                FROM tasas 
                WHERE PaisOrigenID = ? AND PaisDestinoID = ? 
                AND Activa = 1 ";
        if ($montoOrigen > 0) {
            $sql .= " AND ? >= MontoMinimo AND ? <= MontoMaximo ";
            $sql .= " ORDER BY FechaEfectiva DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iidd", $origenID, $destinoID, $montoOrigen, $montoOrigen);
        } else {
            $sql .= " ORDER BY MontoMinimo ASC, FechaEfectiva DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $origenID, $destinoID);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }

    public function updateRateValue(int $tasaId, float $nuevoValor, float $montoMin, float $montoMax): bool
    {
        $sql = "UPDATE tasas SET ValorTasa = ?, MontoMinimo = ?, MontoMaximo = ?, FechaEfectiva = NOW() WHERE TasaID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("dddi", $nuevoValor, $montoMin, $montoMax, $tasaId);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }

    public function createRate(int $origenId, int $destinoId, float $valor, float $montoMin, float $montoMax): int
    {
        // Al crear, por defecto Activa es 1
        $sql = "INSERT INTO tasas (PaisOrigenID, PaisDestinoID, ValorTasa, MontoMinimo, MontoMaximo, FechaEfectiva, Activa) 
                VALUES (?, ?, ?, ?, ?, NOW(), 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iidds", $origenId, $destinoId, $valor, $montoMin, $montoMax);
        
        if (!$stmt->execute()) {
             throw new Exception("Error al crear la nueva tasa: " . $stmt->error);
        }
        $newId = $stmt->insert_id;
        $stmt->close();
        return $newId;
    }

    public function logRateChange(int $tasaId, int $origenId, int $destinoId, float $valor, float $montoMin, float $montoMax): bool
    {
        $sql = "INSERT INTO tasas_historico (TasaID_Referencia, PaisOrigenID, PaisDestinoID, ValorTasa, MontoMinimo, MontoMaximo, FechaCambio) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiddss", $tasaId, $origenId, $destinoId, $valor, $montoMin, $montoMax);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function delete(int $tasaId): bool
    {
        $sql = "UPDATE tasas SET Activa = 0 WHERE TasaID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $tasaId);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function checkOverlap(int $origenId, int $destinoId, float $min, float $max, int $excludeTasaId = 0): bool
    {
        $sql = "SELECT TasaID FROM tasas 
                WHERE PaisOrigenID = ? 
                AND PaisDestinoID = ? 
                AND TasaID != ?
                AND Activa = 1
                AND (MontoMinimo <= ? AND MontoMaximo >= ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiidd", $origenId, $destinoId, $excludeTasaId, $max, $min);
        
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    public function getMinMaxRates(int $origenID, int $destinoID): ?array
    {
        $sql = "SELECT MIN(ValorTasa) as MinTasa, MAX(ValorTasa) as MaxTasa 
                FROM tasas 
                WHERE PaisOrigenID = ? AND PaisDestinoID = ? AND Activa = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $origenID, $destinoID);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }
}