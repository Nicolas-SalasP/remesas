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
                AND ? >= MontoMinimo AND ? <= MontoMaximo
                ORDER BY FechaEfectiva DESC LIMIT 1"; 
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iidd", $origenID, $destinoID, $montoOrigen, $montoOrigen);
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
        $sql = "INSERT INTO tasas (PaisOrigenID, PaisDestinoID, ValorTasa, MontoMinimo, MontoMaximo, FechaEfectiva) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iidds", $origenId, $destinoId, $valor, $montoMin, $montoMax);
        
        if (!$stmt->execute()) {
             throw new Exception("Error al crear la nueva tasa: " . $stmt->error);
        }
        $newId = $stmt->insert_id;
        $stmt->close();
        return $newId;
    }
}