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

    public function getRateHistoryByDate(int $origenId, int $destinoId, int $days = 30): array
    {
        $sql = "SELECT
                    DATE(FechaCambio) AS Fecha,
                    AVG(ValorTasa) AS TasaPromedio
                FROM tasas_historico
                WHERE
                    PaisOrigenID = ?
                    AND PaisDestinoID = ?
                    AND FechaCambio >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                    AND MontoMinimo = 0.00
                GROUP BY
                    Fecha
                ORDER BY
                    Fecha ASC
                LIMIT ?";
        
        $limit = $days + 5;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiii", $origenId, $destinoId, $days, $limit);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
}