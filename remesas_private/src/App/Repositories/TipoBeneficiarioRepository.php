<?php
namespace App\Repositories;

use App\Database\Database;

class TipoBeneficiarioRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findIdByName(string $nombreTipo): ?int
    {
        $sql = "SELECT TipoBeneficiarioID FROM tipos_beneficiario WHERE Nombre = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $nombreTipo);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['TipoBeneficiarioID'] ?? null;
    }

    public function findAllActive(): array
    {
        $sql = "SELECT TipoBeneficiarioID, Nombre FROM tipos_beneficiario WHERE Activo = TRUE ORDER BY Nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
}