<?php

namespace App\Services;

use App\Database\Database;

class LogService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function logAction(?int $userId, string $action, string $details): void
    {
        $sql = "INSERT INTO logs (UserID, Accion, Detalles) VALUES (?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("iss", $userId, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}