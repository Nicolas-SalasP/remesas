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
        $sql = "INSERT INTO transacciones (UserID, CuentaBeneficiariaID, TasaID_Al_Momento, MontoOrigen, MonedaOrigen, MontoDestino, MonedaDestino, EstadoID, FormaPagoID)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $estadoInicialID = $data['estadoID'] ?? 1;

        $stmt->bind_param("iiidssdis",
            $data['userID'],
            $data['cuentaID'],
            $data['tasaID'],
            $data['montoOrigen'],
            $data['monedaOrigen'],
            $data['montoDestino'],
            $data['monedaDestino'],
            $estadoInicialID,
            $data['formaPagoID']
        );

        if (!$stmt->execute()) {
             error_log("Error al crear la transacción: " . $stmt->error . " - Data: " . print_r($data, true));
             throw new Exception("No se pudo registrar la orden. Inténtalo de nuevo.");
        }
        $newId = $stmt->insert_id;
        $stmt->close();
        return $newId;
    }

    public function getFullTransactionDetails(int $transactionId): ?array
    {
        $sql = "SELECT
            T.TransaccionID, T.UserID, T.CuentaBeneficiariaID, T.TasaID_Al_Momento,
            T.MontoOrigen, T.MonedaOrigen, T.MontoDestino, T.MonedaDestino,
            T.FechaTransaccion, T.ComprobanteURL, T.ComprobanteEnvioURL,
            U.PrimerNombre, U.PrimerApellido, U.Email, U.NumeroDocumento, U.Telefono,
            TD_U.NombreDocumento AS UsuarioTipoDocumentoNombre,
            R.NombreRol AS UsuarioRolNombre,
            EV.NombreEstado AS UsuarioVerificacionEstadoNombre,
            CB.Alias AS BeneficiarioAlias, CB.TitularPrimerNombre, CB.TitularPrimerApellido,
            CB.TitularNumeroDocumento, CB.NombreBanco, CB.NumeroCuenta, CB.NumeroTelefono AS BeneficiarioTelefono,
            TD_B.NombreDocumento AS BeneficiarioTipoDocumentoNombre,
            TB.Nombre AS BeneficiarioTipoNombre,
            TS.ValorTasa,
            ET.EstadoID, ET.NombreEstado AS Estado,
            FP.FormaPagoID, FP.Nombre AS FormaDePago
        FROM transacciones AS T
        JOIN usuarios AS U ON T.UserID = U.UserID
        JOIN cuentas_beneficiarias AS CB ON T.CuentaBeneficiariaID = CB.CuentaID
        JOIN tasas AS TS ON T.TasaID_Al_Momento = TS.TasaID
        LEFT JOIN estados_transaccion AS ET ON T.EstadoID = ET.EstadoID
        LEFT JOIN formas_pago AS FP ON T.FormaPagoID = FP.FormaPagoID
        LEFT JOIN tipos_documento AS TD_U ON U.TipoDocumentoID = TD_U.TipoDocumentoID
        LEFT JOIN roles AS R ON U.RolID = R.RolID
        LEFT JOIN estados_verificacion AS EV ON U.VerificacionEstadoID = EV.EstadoID
        LEFT JOIN tipos_documento AS TD_B ON CB.TitularTipoDocumentoID = TD_B.TipoDocumentoID
        LEFT JOIN tipos_beneficiario AS TB ON CB.TipoBeneficiarioID = TB.TipoBeneficiarioID
        WHERE T.TransaccionID = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function uploadUserReceipt(int $transactionId, int $userId, string $dbPath, int $estadoEnVerificacionID, int $estadoPendienteID): int
    {
        $sql = "UPDATE transacciones SET ComprobanteURL = ?, EstadoID = ?
                WHERE TransaccionID = ? AND UserID = ? AND EstadoID IN (?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bind_param("siiiii", 
            $dbPath, 
            $estadoEnVerificacionID,
            $transactionId,
            $userId,
            $estadoPendienteID,
            $estadoEnVerificacionID
        );

        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }

    public function uploadAdminProof(int $transactionId, string $dbPath, int $estadoPagadoID, int $estadoEnProcesoID): int
    {
        $sql = "UPDATE transacciones SET ComprobanteEnvioURL = ?, EstadoID = ?
                WHERE TransaccionID = ? AND EstadoID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("siii", $dbPath, $estadoPagadoID, $transactionId, $estadoEnProcesoID);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }

    public function updateStatus(int $id, int $newStatusID, ?int $requiredStatusID = null): int
    {
        $sql = "UPDATE transacciones SET EstadoID = ? WHERE TransaccionID = ?";
        $paramTypes = "ii";
        $params = [$newStatusID, $id];

        if ($requiredStatusID !== null) {
            $sql .= " AND EstadoID = ?";
            $paramTypes .= "i";
            $params[] = $requiredStatusID;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }

    public function cancel(int $transactionId, int $userId, int $estadoCanceladoID, int $estadoPendienteID): int
    {
        $sql = "UPDATE transacciones SET EstadoID = ?
                WHERE TransaccionID = ? AND UserID = ? AND EstadoID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iiii", $estadoCanceladoID, $transactionId, $userId, $estadoPendienteID);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }

     // --- MÉTODOS PARA ESTADÍSTICAS  ---

    public function countByStatus(array $statusIDs): int
    {
        if (empty($statusIDs)) return 0;
        $placeholders = implode(',', array_fill(0, count($statusIDs), '?'));
        $sql = "SELECT COUNT(TransaccionID) as total FROM transacciones WHERE EstadoID IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($statusIDs)), ...$statusIDs);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($result['total'] ?? 0);
    }

    public function countCompletedToday(int $estadoPagadoID): int
    {
        $sql = "SELECT COUNT(TransaccionID) as total FROM transacciones WHERE EstadoID = ? AND DATE(FechaTransaccion) = CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $estadoPagadoID);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($result['total'] ?? 0);
    }

    public function getTotalVolume(int $estadoPagadoID): float
    {
        $sql = "SELECT SUM(MontoOrigen) as total_volumen FROM transacciones WHERE EstadoID = ?";
        $stmt = $this->db->prepare($sql);
         $stmt->bind_param("i", $estadoPagadoID);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (float)($result['total_volumen'] ?? 0.0);
    }

    public function findEstadoTransaccionIdByName(string $nombreEstado): ?int
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