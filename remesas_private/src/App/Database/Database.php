<?php
namespace App\Database;

use mysqli;
use Exception;

class Database
{
    private static ?Database $instance = null;
    private mysqli $connection;

    private function __construct()
    {
        try {
            $this->connection = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión a la base de datos: " . $this->connection->connect_error);
            }
            $this->connection->set_charset("utf8mb4");

        } catch (Exception $e) {
            error_log($e->getMessage());
            die("Error interno del servidor al conectar con la base de datos. Contacte al soporte.");
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }

    public function __clone() {}

    public function __wakeup() {}

    public function prepare(string $sql): \mysqli_stmt
    {
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
             throw new Exception("Error al preparar la consulta: " . $this->connection->error);
        }
        return $stmt;
    }
}