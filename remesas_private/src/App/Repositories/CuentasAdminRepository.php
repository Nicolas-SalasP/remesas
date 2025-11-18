<?php
namespace App\Repositories;

use App\Database\Database;
use Exception;

class CuentasAdminRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        $sql = "SELECT c.*, f.Nombre as FormaPagoNombre 
                FROM cuentas_bancarias_admin c
                JOIN formas_pago f ON c.FormaPagoID = f.FormaPagoID
                ORDER BY f.Nombre, c.Banco";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function findByFormaPagoId(int $formaPagoId): ?array
    {
        $sql = "SELECT * FROM cuentas_bancarias_admin WHERE FormaPagoID = ? AND Activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $formaPagoId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO cuentas_bancarias_admin (FormaPagoID, Banco, Titular, TipoCuenta, NumeroCuenta, RUT, Email, Instrucciones, ColorHex) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "issssssss",
            $data['formaPagoId'],
            $data['banco'],
            $data['titular'],
            $data['tipoCuenta'],
            $data['numeroCuenta'],
            $data['rut'],
            $data['email'],
            $data['instrucciones'],
            $data['colorHex']
        );
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE cuentas_bancarias_admin SET FormaPagoID=?, Banco=?, Titular=?, TipoCuenta=?, NumeroCuenta=?, RUT=?, Email=?, Instrucciones=?, ColorHex=?, Activo=? WHERE CuentaAdminID=?";
        $stmt = $this->db->prepare($sql);
        $activoInt = (int) $data['activo'];
        $stmt->bind_param(
            "issssssssii",
            $data['formaPagoId'],
            $data['banco'],
            $data['titular'],
            $data['tipoCuenta'],
            $data['numeroCuenta'],
            $data['rut'],
            $data['email'],
            $data['instrucciones'],
            $data['colorHex'],
            $activoInt,
            $id
        );
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM cuentas_bancarias_admin WHERE CuentaAdminID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}