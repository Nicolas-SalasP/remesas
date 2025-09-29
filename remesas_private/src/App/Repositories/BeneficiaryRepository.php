<?php

namespace App\Repositories;

use App\Database\Database;
use Exception;

class BeneficiaryRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findAccountsByUserIdAndCountry(int $userId, int $paisId): array
    {
        $sql = "SELECT CuentaID, Alias 
                FROM cuentasbeneficiarias 
                WHERE UserID = ? AND PaisID = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $userId, $paisId);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_all(\MYSQLI_ASSOC);
        $stmt->close();
        
        return $result;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO cuentasbeneficiarias (UserID, PaisID, Alias, TipoBeneficiario, TitularPrimerNombre, TitularSegundoNombre, TitularPrimerApellido, TitularSegundoApellido, TitularTipoDocumento, TitularNumeroDocumento, NombreBanco, NumeroCuenta, NumeroTelefono) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        // El formato de bind_param es iisssssssssss (i: int, s: string)
        $stmt->bind_param("iisssssssssss", 
            $data['UserID'], 
            $data['paisID'], 
            $data['alias'], 
            $data['tipoBeneficiario'], 
            $data['primerNombre'], 
            $data['segundoNombre'], 
            $data['primerApellido'], 
            $data['segundoApellido'], 
            $data['tipoDocumento'], 
            $data['numeroDocumento'], 
            $data['nombreBanco'], 
            $data['numeroCuenta'], 
            $data['numeroTelefono']
        );

        if (!$stmt->execute()) {
             throw new Exception("Error al registrar cuenta beneficiaria: " . $stmt->error);
        }
        $newId = $stmt->insert_id;
        $stmt->close();
        return $newId;
    }
}