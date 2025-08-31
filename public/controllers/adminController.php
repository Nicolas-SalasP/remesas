<?php

class AdminController {
    private $db;

    public function __construct(mysqli $conexion) {
        $this->db = $conexion;
    }

    /**
     * Obtiene la lista de todas las tasas de cambio para gestionarlas.
     */
    public function getTasas() {
        // En un futuro, esta función devolvería todas las tasas para una tabla de admin
        echo json_encode(['success' => true, 'message' => 'Función para obtener tasas aún no implementada.']);
    }

    /**
     * Actualiza o crea una nueva tasa de cambio.
     */
    public function updateTasa() {
        $data = json_decode(file_get_contents('php://input'), true);

        // Validación básica de los datos recibidos
        if (!isset($data['origenID'], $data['destinoID'], $data['valorTasa'])) {
            echo json_encode(['success' => false, 'error' => 'Faltan datos para actualizar la tasa.']);
            return;
        }

        $origenID = $data['origenID'];
        $destinoID = $data['destinoID'];
        $valorTasa = $data['valorTasa'];
        $fechaEfectiva = date('Y-m-d'); // La tasa es efectiva desde hoy

        // Preparamos la inserción de la nueva tasa
        $stmt = $this->db->prepare("INSERT INTO Tasas (PaisOrigenID, PaisDestinoID, ValorTasa, FechaEfectiva) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iids", $origenID, $destinoID, $valorTasa, $fechaEfectiva);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Tasa actualizada correctamente.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar la tasa: ' . $stmt->error]);
        }
        $stmt->close();
    }
}