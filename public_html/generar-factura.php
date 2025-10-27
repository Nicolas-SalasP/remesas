<?php
require_once __DIR__ . '/../remesas_private/src/core/init.php';
require_once __DIR__ . '/../remesas_private/src/lib/fpdf/fpdf.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Acceso denegado. Debes iniciar sesión.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || $_GET['id'] <= 0) {
    http_response_code(400);
    die("ID de transacción no válido.");
}
$transactionId = (int)$_GET['id'];
$userId = (int)$_SESSION['user_id'];
$userRol = $_SESSION['user_rol_name'] ?? 'Persona Natural';

global $conexion;
if (!$conexion) {
     http_response_code(500);
     die("Error interno del servidor al conectar con la base de datos.");
}

$sql = "SELECT
            T.TransaccionID, T.FechaTransaccion, T.MontoOrigen, T.MonedaOrigen, T.MontoDestino, T.MonedaDestino,
            U.PrimerNombre, U.PrimerApellido, U.Email, U.NumeroDocumento,
            CB.TitularPrimerNombre, CB.TitularPrimerApellido, CB.TitularNumeroDocumento, CB.NombreBanco, CB.NumeroCuenta,
            TS.ValorTasa,
            ET.NombreEstado AS Estado
        FROM transacciones AS T
        JOIN usuarios AS U ON T.UserID = U.UserID
        JOIN cuentas_beneficiarias AS CB ON T.CuentaBeneficiariaID = CB.CuentaID
        LEFT JOIN tasas AS TS ON T.TasaID_Al_Momento = TS.TasaID
        LEFT JOIN estados_transaccion AS ET ON T.EstadoID = ET.EstadoID
        WHERE T.TransaccionID = ?";

$params = [$transactionId];
$types = "i";

if ($userRol !== 'Admin' && $userRol !== 'Operador') {
    $sql .= " AND T.UserID = ?";
    $params[] = $userId;
    $types .= "i";
}

$stmt = $conexion->prepare($sql);
if (!$stmt) {
     error_log("Error al preparar la consulta para generar factura: ". $conexion->error);
     http_response_code(500);
     die("Error interno al preparar la consulta.");
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();
$tx = $resultado->fetch_assoc();
$stmt->close();

if (!$tx) {
    http_response_code(404);
    die("Transacción no encontrada o no tienes permiso para verla.");
}

try {
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, mb_convert_encoding('ORDEN DE ENVÍO DE DINERO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, mb_convert_encoding('Comprobante de Transacción', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    $pdf->Ln(8);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(40, 7, mb_convert_encoding('Nro. Orden:', 'ISO-8859-1', 'UTF-8'), 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, htmlspecialchars($tx['TransaccionID']), 0, 1);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(40, 7, 'Fecha:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, date("d/m/Y H:i", strtotime($tx['FechaTransaccion'])), 0, 1);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(40, 7, 'Estado:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, mb_convert_encoding(htmlspecialchars($tx['Estado'] ?? 'Desconocido'), 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(8);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(90, 8, 'DATOS DEL REMITENTE', 1, 0, 'C', true);
    $pdf->Cell(90, 8, 'DATOS DEL BENEFICIARIO', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    $fill = false;
    $border = 'LR';
    $printDataRow = function($labelRem, $valueRem, $labelBen, $valueBen, $isLast = false) use ($pdf, $border, $fill) {
        $currentBorder = $border . ($isLast ? 'B' : '');
        $pdf->Cell(30, 6, mb_convert_encoding($labelRem, 'ISO-8859-1', 'UTF-8'), $currentBorder, 0, 'L', $fill);
        $pdf->Cell(60, 6, mb_convert_encoding(htmlspecialchars($valueRem), 'ISO-8859-1', 'UTF-8'), $currentBorder, 0, 'L', $fill);
        $pdf->Cell(30, 6, mb_convert_encoding($labelBen, 'ISO-8859-1', 'UTF-8'), $currentBorder, 0, 'L', $fill);
        $pdf->Cell(60, 6, mb_convert_encoding(htmlspecialchars($valueBen), 'ISO-8859-1', 'UTF-8'), $currentBorder, 1, 'L', $fill);
    };

    $printDataRow('Nombre:', $tx['PrimerNombre'] . ' ' . $tx['PrimerApellido'], 'Nombre:', $tx['TitularPrimerNombre'] . ' ' . $tx['TitularPrimerApellido']);
    $printDataRow('Documento:', $tx['NumeroDocumento'], 'Documento:', $tx['TitularNumeroDocumento']);
    $printDataRow('Email:', $tx['Email'], 'Banco:', $tx['NombreBanco']);
    $printDataRow('', '', 'Cuenta:', $tx['NumeroCuenta'], true);
    $pdf->Ln(8);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(0, 9, mb_convert_encoding('RESUMEN DE LA TRANSACCIÓN', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', 'B', 10);
    $cellWidths = [60, 60, 60];
    $pdf->Cell($cellWidths[0], 7, 'Monto Enviado', 1, 0, 'C', true);
    $pdf->Cell($cellWidths[1], 7, 'Tasa Aplicada', 1, 0, 'C', true);
    $pdf->Cell($cellWidths[2], 7, 'Monto a Recibir', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell($cellWidths[0], 10, number_format($tx['MontoOrigen'], 2, ',', '.') . ' ' . htmlspecialchars($tx['MonedaOrigen']), 1, 0, 'C');
    $tasaMostrada = number_format($tx['ValorTasa'] ?? 0, 4, ',', '.');
    $pdf->Cell($cellWidths[1], 10, $tasaMostrada . ' ' . htmlspecialchars($tx['MonedaDestino']) . '/' . htmlspecialchars($tx['MonedaOrigen']), 1, 0, 'C');
    $pdf->Cell($cellWidths[2], 10, number_format($tx['MontoDestino'], 2, ',', '.') . ' ' . htmlspecialchars($tx['MonedaDestino']), 1, 1, 'C');
    $pdf->Ln(15);

    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(128);
    $pdf->Cell(0, 10, mb_convert_encoding('Gracias por usar nuestros servicios. Este es un comprobante informativo.', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');

    if (ob_get_level()) {
        ob_end_clean();
    }
    
    $pdf->Output('I', 'orden-'.$tx['TransaccionID'].'.pdf');
    exit;

} catch (Exception $e) {
    error_log("Error al generar PDF para TX ID {$transactionId}: " . $e->getMessage());
    http_response_code(500);
    die("Error interno al generar el comprobante PDF.");
}
?>