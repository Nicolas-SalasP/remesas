<?php

namespace App\Repositories;

use App\Database\Database;
use Exception;

class TransactionRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO transacciones (UserID, CuentaBeneficiariaID, TasaID_Al_Momento, MontoOrigen, MonedaOrigen, MontoDestino, MonedaDestino, Estado, FormaDePago) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente de Pago', ?)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bind_param("iiidssdss", 
            $data['userID'], 
            $data['cuentaID'], 
            $data['tasaID'], 
            $data['montoOrigen'],
            $data['monedaOrigen'], 
            $data['montoDestino'], 
            $data['monedaDestino'], 
            $data['formaDePago']
        );

        if (!$stmt->execute()) {
             throw new Exception("Error al crear la transacción: " . $stmt->error);
        }
        $newId = $stmt->insert_id;
        $stmt->close();
        return $newId;
    }

    public function uploadUserReceipt(int $transactionId, int $userId, string $dbPath): int
    {
        $sql = "UPDATE transacciones SET ComprobanteURL = ?, Estado = 'En Verificación' 
                WHERE TransaccionID = ? AND UserID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sii", $dbPath, $transactionId, $userId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }

    public function uploadAdminProof(int $transactionId, string $dbPath): int
    {
        $sql = "UPDATE transacciones SET ComprobanteEnvioURL = ?, Estado = 'Pagado' 
                WHERE TransaccionID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $dbPath, $transactionId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }

    public function updateStatus(int $id, string $newStatus, ?string $requiredStatus = null): int
    {
        $sql = "UPDATE transacciones SET Estado = ? WHERE TransaccionID = ?";
        $paramTypes = "si";
        $params = [$newStatus, $id];

        if ($requiredStatus) {
            $sql .= " AND Estado = ?";
            $paramTypes .= "s";
            $params[] = $requiredStatus;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }
    
    public function cancel(int $transactionId, int $userId): int
    {
        $sql = "UPDATE transacciones SET Estado = 'Cancelado' 
                WHERE TransaccionID = ? AND UserID = ? AND Estado = 'Pendiente de Pago'";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $transactionId, $userId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }
}