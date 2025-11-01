<?php
namespace App\Repositories;

use App\Database\Database;

class TipoDocumentoRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findAllActive(): array
    {
        $sql = "SELECT TipoDocumentoID, NombreDocumento FROM tipos_documento WHERE Activo = TRUE ORDER BY NombreDocumento";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function findIdByName(string $nombreDocumento): ?int
    {
         $sql = "SELECT TipoDocumentoID FROM tipos_documento WHERE NombreDocumento = ? LIMIT 1";
         $stmt = $this->db->prepare($sql);
         $stmt->bind_param("s", $nombreDocumento);
         $stmt->execute();
         $result = $stmt->get_result()->fetch_assoc();
         $stmt->close();
         return $result['TipoDocumentoID'] ?? null;
    }
}