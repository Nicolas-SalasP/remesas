<?php
require_once __DIR__ . '/../../remesas_private/vendor/autoload.php';
require_once __DIR__ . '/../../remesas_private/src/core/init.php';

use App\Database\Database;
use App\Repositories\TransactionRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

if (!isset($_SESSION['user_rol_name']) || $_SESSION['user_rol_name'] !== 'Admin') {
    die("Acceso denegado.");
}

try {
    $db = Database::getInstance();
    $txRepository = new TransactionRepository($db);

    $data = $txRepository->getExportData();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Transacciones');

    $headers = [
        'ID Transaccion', 'Fecha', 'Estado', 'Cliente', 'Email Cliente',
        'Monto Origen', 'Moneda Origen', 'Tasa Aplicada', 'Monto Destino',
        'Comision (Destino)', 'Moneda Destino', 'Beneficiario', 'Documento Beneficiario',
        'Banco Beneficiario', 'Cuenta Beneficiario'
    ];
    $sheet->fromArray($headers, NULL, 'A1');

    $rowNumber = 2;
    foreach ($data as $row) {
        $sheet->setCellValue('A' . $rowNumber, $row['TransaccionID']);
        
        $sheet->setCellValue('B' . $rowNumber, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($row['FechaTransaccion']));
        $sheet->getStyle('B' . $rowNumber)->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
        
        $sheet->setCellValue('C' . $rowNumber, $row['Estado']);
        $sheet->setCellValue('D' . $rowNumber, $row['ClienteNombre']);
        $sheet->setCellValue('E' . $rowNumber, $row['ClienteEmail']);
        
        $sheet->setCellValue('F' . $rowNumber, (float)$row['MontoOrigen']);
        $sheet->getStyle('F' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00');
        
        $sheet->setCellValue('G' . $rowNumber, $row['MonedaOrigen']);
        
        $sheet->setCellValue('H' . $rowNumber, (float)$row['ValorTasa']);
        $sheet->getStyle('H' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.000000');
        
        $sheet->setCellValue('I' . $rowNumber, (float)$row['MontoDestino']);
        $sheet->getStyle('I' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->setCellValue('J' . $rowNumber, (float)$row['ComisionDestino']);
        $sheet->getStyle('J' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->setCellValue('K' . $rowNumber, $row['MonedaDestino']);
        
        $sheet->setCellValue('L' . $rowNumber, $row['BeneficiarioNombre']);
        
        $sheet->setCellValueExplicit('M' . $rowNumber, $row['BeneficiarioDocumento'], DataType::TYPE_STRING);
        $sheet->setCellValue('N' . $rowNumber, $row['BeneficiarioBanco']);

        $sheet->setCellValueExplicit('O' . $rowNumber, $row['BeneficiarioNumeroCuenta'], DataType::TYPE_STRING);

        
        $rowNumber++;
    }

    foreach (range('A', 'O') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $filename = "Reporte_Transacciones_" . date('Y-m-d') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    
    ob_end_clean();
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    error_log("Error al exportar transacciones XLSX: " . $e->getMessage());
    die("Error interno al generar el reporte: " . $e->getMessage());
}
?>