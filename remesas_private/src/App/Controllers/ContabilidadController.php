<?php
namespace App\Controllers;

use App\Services\ContabilidadService;
use Exception;

class ContabilidadController extends BaseController
{
    private ContabilidadService $contabilidadService;

    public function __construct(ContabilidadService $contabilidadService)
    {
        $this->contabilidadService = $contabilidadService;
        $this->ensureAdmin();
    }

    public function getSaldos(): void
    {
        $saldos = $this->contabilidadService->getSaldosDashboard();
        $this->sendJsonResponse(['success' => true, 'saldos' => $saldos]);
    }

    public function agregarFondos(): void
    {
        $adminId = $this->ensureLoggedIn();
        $data = $this->getJsonInput();
        
        $paisId = (int)($data['paisId'] ?? 0);
        $monto = (float)($data['monto'] ?? 0);
        $tipo = (string)($data['tipo'] ?? 'recarga');
        
        if ($paisId <= 0 || $monto <= 0 || !in_array($tipo, ['recarga', 'inicial'])) {
            throw new Exception("Datos incompletos o inválidos.", 400);
        }
        
        $this->contabilidadService->agregarFondos($paisId, $monto, $adminId, $tipo);
        $this->sendJsonResponse(['success' => true, 'message' => 'Fondos agregados con éxito.']);
    }
    
    public function getResumenMensual(): void
    {
        $paisId = (int)($_GET['paisId'] ?? 0);
        $mes = (int)($_GET['mes'] ?? 0);
        $anio = (int)($_GET['anio'] ?? 0);

        if ($paisId <= 0 || $mes <= 0 || $anio <= 2020) {
            throw new Exception("Parámetros de fecha o país inválidos.", 400);
        }
        
        $resumen = $this->contabilidadService->getResumenMensual($paisId, $mes, $anio);
        $this->sendJsonResponse(['success' => true, 'resumen' => $resumen]);
    }
}