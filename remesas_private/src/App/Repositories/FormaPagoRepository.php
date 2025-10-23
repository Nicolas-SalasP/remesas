<?php
namespace App\Repositories;

use App\Database\Database;

class FormaPagoRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findIdByName(string $nombreFormaPago): ?int
    {
        $sql = "SELECT FormaPagoID FROM formas_pago WHERE Nombre = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $nombreFormaPago);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['FormaPagoID'] ?? null;
    }

    public function findAllActive(): array
    {
        $sql = "SELECT FormaPagoID, Nombre FROM formas_pago WHERE Activo = TRUE ORDER BY Nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
}