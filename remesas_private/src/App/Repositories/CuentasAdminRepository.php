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
        $sql = "SELECT c.*, f.Nombre as FormaPagoNombre, p.NombrePais 
                FROM cuentas_bancarias_admin c
                JOIN formas_pago f ON c.FormaPagoID = f.FormaPagoID
                JOIN paises p ON c.PaisID = p.PaisID
                ORDER BY p.NombrePais, f.Nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function findByFormaPagoAndPais(int $formaPagoId, int $paisId): ?array
    {
        $sql = "SELECT * FROM cuentas_bancarias_admin WHERE FormaPagoID = ? AND PaisID = ? AND Activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $formaPagoId, $paisId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO cuentas_bancarias_admin (FormaPagoID, PaisID, Banco, Titular, TipoCuenta, NumeroCuenta, RUT, Email, Instrucciones, ColorHex) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iissssssss", 
            $data['formaPagoId'], $data['paisId'], $data['banco'], $data['titular'], $data['tipoCuenta'], 
            $data['numeroCuenta'], $data['rut'], $data['email'], $data['instrucciones'], $data['colorHex']
        );
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE cuentas_bancarias_admin SET FormaPagoID=?, PaisID=?, Banco=?, Titular=?, TipoCuenta=?, NumeroCuenta=?, RUT=?, Email=?, Instrucciones=?, ColorHex=?, Activo=? WHERE CuentaAdminID=?";
        $stmt = $this->db->prepare($sql);
        $activoInt = (int)$data['activo'];
        $stmt->bind_param("iissssssssii", 
            $data['formaPagoId'], $data['paisId'], $data['banco'], $data['titular'], $data['tipoCuenta'], 
            $data['numeroCuenta'], $data['rut'], $data['email'], $data['instrucciones'], 
            $data['colorHex'], $activoInt, $id
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