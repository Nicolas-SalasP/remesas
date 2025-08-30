<?php

class TransactionController {
    private $db; // Para guardar la conexión a la base de datos

    // El constructor recibe la conexión a la BD
    public function __construct(mysqli $conexion) {
        $this->db = $conexion;
    }

    /**
     * Obtiene la lista de países según su rol (Origen o Destino).
     */
    public function getPaises() {
        $rol = $_GET['rol'] ?? 'Ambos';
        $stmt = $this->db->prepare("SELECT PaisID, NombrePais FROM Paises WHERE Rol = ? OR Rol = 'Ambos'");
        $stmt->bind_param("s", $rol);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $paises = $resultado->fetch_all(MYSQLI_ASSOC);
        echo json_encode($paises);
        $stmt->close();
    }

    /**
     * Obtiene las cuentas de beneficiario de un usuario para un país específico.
     */
    public function getCuentas() {
        $userID = $_GET['userID'] ?? 0;
        $paisID = $_GET['paisID'] ?? 0;
        $stmt = $this->db->prepare("SELECT CuentaID, Alias FROM CuentasBeneficiarias WHERE UserID = ? AND PaisID = ?");
        $stmt->bind_param("ii", $userID, $paisID);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $cuentas = $resultado->fetch_all(MYSQLI_ASSOC);
        echo json_encode($cuentas);
        $stmt->close();
    }

    /**
     * Obtiene la tasa de cambio más reciente para una ruta (origen -> destino).
     */
    public function getTasa() {
        $origenID = $_GET['origenID'] ?? 0;
        $destinoID = $_GET['destinoID'] ?? 0;
        $stmt = $this->db->prepare("SELECT TasaID, ValorTasa FROM Tasas WHERE PaisOrigenID = ? AND PaisDestinoID = ? ORDER BY FechaEfectiva DESC LIMIT 1");
        $stmt->bind_param("ii", $origenID, $destinoID);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $tasa = $resultado->fetch_assoc();
        echo json_encode($tasa);
        $stmt->close();
    }
    
    /**
     * Crea una nueva transacción en la base de datos.
     */
    public function create() {
        $data = json_decode(file_get_contents('php://input'), true);
        // (Aquí iría la validación de datos para la transacción)
        $stmt = $this->db->prepare("INSERT INTO Transacciones (UserID, CuentaBeneficiariaID, TasaID_Al_Momento, MontoOrigen, MonedaOrigen, MontoDestino, MonedaDestino, Estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pendiente de Pago')");
        $stmt->bind_param("iiidssd", 
            $data['userID'], $data['cuentaID'], $data['tasaID'],
            $data['montoOrigen'], $data['monedaOrigen'],
            $data['montoDestino'], $data['monedaDestino']
        );
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'transaccionID' => $this->db->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    }
    
    /**
     * Obtiene el valor actual del Dólar BCV haciendo web scraping.
     */
    public function getBcvRate() {
        $url = 'https://www.bcv.org.ve/';
        $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $html = @file_get_contents($url, false, $context);

        if ($html === false) {
            echo json_encode(['success' => false, 'error' => 'No se pudo conectar con el sitio del BCV.']);
            return;
        }

        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $query = "//div[@id='dolar']//strong";
        $nodos = $xpath->query($query);

        if ($nodos->length > 0) {
            $valorString = $nodos[0]->nodeValue;
            $valorLimpio = str_replace(',', '.', trim($valorString));
            $valorFloat = (float)$valorLimpio;
            echo json_encode(['success' => true, 'valor' => $valorFloat]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se pudo encontrar el valor del dólar en la página del BCV.']);
        }
    }
}