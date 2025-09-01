<?php
// Carga la configuración, inicia sesión y conecta a la BD.
require_once __DIR__ . '/../src/core/init.php';
// Carga la librería FPDF
require_once __DIR__ . '/../src/lib/fpdf/fpdf.php';

// 1. --- VERIFICACIÓN DE SEGURIDAD ---
if (!isset($_SESSION['user_id'])) {
    die("Acceso denegado. Debes iniciar sesión.");
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de transacción no válido.");
}
$transactionId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// 2. --- OBTENER TODOS LOS DATOS DE LA TRANSACCIÓN ---
$sql = "SELECT 
            T.*, 
            U.PrimerNombre, U.PrimerApellido, U.Email, U.NumeroDocumento,
            CB.TitularPrimerNombre, CB.TitularPrimerApellido, CB.TitularNumeroDocumento, CB.NombreBanco, CB.NumeroCuenta
        FROM Transacciones AS T
        JOIN Usuarios AS U ON T.UserID = U.UserID
        JOIN CuentasBeneficiarias AS CB ON T.CuentaBeneficiariaID = CB.CuentaID
        WHERE T.TransaccionID = ? AND T.UserID = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $transactionId, $userId);
$stmt->execute();
$resultado = $stmt->get_result();
$tx = $resultado->fetch_assoc();
$stmt->close();

if (!$tx) {
    die("Transacción no encontrada o no te pertenece.");
}

// 3. --- GENERACIÓN DEL PDF ---
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Encabezado
$pdf->Cell(0, 10, 'ORDEN DE ENVIO DE DINERO', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Comprobante de Transaccion', 0, 1, 'C');
$pdf->Ln(10); // Salto de línea

// Datos de la transacción
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 7, 'Nro. Orden:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, $tx['TransaccionID'], 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 7, 'Fecha:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, date("d/m/Y H:i", strtotime($tx['FechaTransaccion'])), 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 7, 'Estado:', 0, 0);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 7, $tx['Estado'], 0, 1);
$pdf->Ln(10);

// Datos del Remitente y Beneficiario
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(95, 7, 'DATOS DEL REMITENTE', 1, 0, 'C');
$pdf->Cell(0, 7, 'DATOS DEL BENEFICIARIO', 1, 1, 'C');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 7, 'Nombre: ' . $tx['PrimerNombre'] . ' ' . $tx['PrimerApellido'], 'LR', 0);
$pdf->Cell(0, 7, 'Nombre: ' . $tx['TitularPrimerNombre'] . ' ' . $tx['TitularPrimerApellido'], 'R', 1);
$pdf->Cell(95, 7, 'Documento: ' . $tx['NumeroDocumento'], 'LR', 0);
$pdf->Cell(0, 7, 'Documento: ' . $tx['TitularNumeroDocumento'], 'R', 1);
$pdf->Cell(95, 7, 'Email: ' . $tx['Email'], 'LRB', 0);
$pdf->Cell(0, 7, 'Banco: ' . $tx['NombreBanco'], 'RB', 1);
$pdf->Cell(95, 7, '', 'LRB', 0);
$pdf->Cell(0, 7, 'Cuenta: ' . $tx['NumeroCuenta'], 'RB', 1);
$pdf->Ln(10);

// Resumen financiero
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'RESUMEN DE LA TRANSACCION', 0, 1, 'C');
$pdf->Cell(63, 7, 'Monto Enviado', 1, 0, 'C');
$pdf->Cell(63, 7, 'Tasa de Cambio', 1, 0, 'C');
$pdf->Cell(64, 7, 'Monto a Recibir', 1, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(63, 10, number_format($tx['MontoOrigen'], 2) . ' ' . $tx['MonedaOrigen'], 1, 0, 'C');
$pdf->Cell(63, 10, '1 CLP = ' . ($tx['MontoDestino'] / $tx['MontoOrigen']) . ' VES', 1, 0, 'C');
$pdf->Cell(64, 10, number_format($tx['MontoDestino'], 2) . ' ' . $tx['MonedaDestino'], 1, 1, 'C');
$pdf->Ln(15);

// Pie de página
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, 'Gracias por usar nuestros servicios.', 0, 0, 'C');

// 4. --- ENVIAR PDF AL NAVEGADOR ---
// 'D' fuerza la descarga, 'I' lo muestra en el navegador.
$pdf->Output('I', 'orden-'.$tx['TransaccionID'].'.pdf');
?>