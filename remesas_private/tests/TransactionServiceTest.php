<?php

use PHPUnit\Framework\TestCase;
use App\Services\TransactionService;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\CuentasBeneficiariasRepository;
use App\Repositories\FormaPagoRepository;
use App\Repositories\EstadoTransaccionRepository;
use App\Services\NotificationService;
use App\Services\PDFService;
use App\Services\FileHandlerService;
use App\Services\ContabilidadService;

class TransactionServiceTest extends TestCase
{
    public function testNoSePuedeCrearTransaccionSiUsuarioNoVerificado()
    {
        $userRepo = $this->createMock(UserRepository::class);
        
        $userRepo->method('findUserById')->willReturn([
            'UserID' => 99,
            'VerificacionEstado' => 'Pendiente', 
            'Telefono' => '+56911111111'
        ]);

        $txRepo = $this->createMock(TransactionRepository::class);
        $cuentasRepo = $this->createMock(CuentasBeneficiariasRepository::class);
        $notifService = $this->createMock(NotificationService::class);
        $pdfService = $this->createMock(PDFService::class);
        $fileHandler = $this->createMock(FileHandlerService::class);
        $estadoTxRepo = $this->createMock(EstadoTransaccionRepository::class);
        $formaPagoRepo = $this->createMock(FormaPagoRepository::class);
        $contabService = $this->createMock(ContabilidadService::class);

        $service = new TransactionService(
            $txRepo, $userRepo, $notifService, $pdfService, 
            $fileHandler, $estadoTxRepo, $formaPagoRepo, 
            $contabService, $cuentasRepo
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Tu cuenta debe estar verificada");

        $service->createTransaction([
            'userID' => 99, 
            'cuentaID' => 1, 
            'montoOrigen' => 50000
        ]);
    }
}