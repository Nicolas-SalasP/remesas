<?php
namespace App\Repositories;

use App\Database\Database;

class EstadoTransaccionRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findIdByName(string $nombreEstado): ?int
    {
        $sql = "SELECT EstadoID FROM estados_transaccion WHERE NombreEstado = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $nombreEstado);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['EstadoID'] ?? null;
    }
}