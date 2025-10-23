<?php
namespace App\Repositories;

use App\Database\Database;

class RolRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findIdByName(string $nombreRol): ?int
    {
        $sql = "SELECT RolID FROM roles WHERE NombreRol = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $nombreRol);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['RolID'] ?? null;
    }

}