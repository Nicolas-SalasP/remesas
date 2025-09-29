<?php

namespace App\Services;

use Exception;
require_once __DIR__ . '/../../lib/fpdf/fpdf.php'; 

class PDFService
{
    public function generateOrder(array $tx): string
    {
        
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        $pdf->Cell(0, 10, 'ORDEN DE ENVIO DE DINERO', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        
        $pdf->Cell(40, 7, 'Nro. Orden:', 0, 0);
        $pdf->Cell(0, 7, $tx['TransaccionID'], 0, 1);
        $pdf->Cell(40, 7, 'Fecha:', 0, 0);
        $pdf->Cell(0, 7, date("d/m/Y H:i", strtotime($tx['FechaTransaccion'])), 0, 1);
        $pdf->Ln(5);

        // Resumen
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(63, 7, 'Monto Enviado', 1, 0, 'C');
        $pdf->Cell(63, 7, 'Tasa de Cambio', 1, 0, 'C');
        $pdf->Cell(64, 7, 'Monto a Recibir', 1, 1, 'C');
        
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(63, 10, number_format($tx['MontoOrigen'], 2) . ' ' . $tx['MonedaOrigen'], 1, 0, 'C');
        $pdf->Cell(63, 10, number_format($tx['ValorTasa'], 4), 1, 0, 'C');
        $pdf->Cell(64, 10, number_format($tx['MontoDestino'], 2) . ' ' . $tx['MonedaDestino'], 1, 1, 'C');

        return $pdf->Output('S'); 
    }
}