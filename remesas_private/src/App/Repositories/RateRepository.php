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

    public function findCurrentRate(int $origenID, int $destinoID): ?array
    {
        $sql = "SELECT TasaID, ValorTasa 
                FROM tasas 
                WHERE PaisOrigenID = ? AND PaisDestinoID = ? 
                ORDER BY FechaEfectiva DESC LIMIT 1"; 
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $origenID, $destinoID);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result;
    }

    public function updateRateValue(int $tasaId, float $nuevoValor): bool
    {
        $sql = "UPDATE tasas SET ValorTasa = ? WHERE TasaID = ?"; 
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("di", $nuevoValor, $tasaId);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }
}