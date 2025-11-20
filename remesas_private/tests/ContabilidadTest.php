<?php

use PHPUnit\Framework\TestCase;
use App\Services\ContabilidadService;
use App\Repositories\ContabilidadRepository;
use App\Repositories\CountryRepository;
use App\Services\LogService;
use App\Database\Database;

class ContabilidadTest extends TestCase
{
    public function testRegistrarGastoDescuentaSaldoCorrectamente()
    {
        $contabRepo = $this->createMock(ContabilidadRepository::class);
        $countryRepo = $this->createMock(CountryRepository::class);
        $logService = $this->createMock(LogService::class);
        
        $db = $this->createMock(Database::class);
        $mysqli = $this->createMock(mysqli::class);
        $db->method('getConnection')->willReturn($mysqli);

        $contabRepo->method('getSaldoPorPais')->willReturn([
            'SaldoID' => 1,
            'SaldoActual' => 1000000.00,
            'PaisID' => 3
        ]);

        $contabRepo->expects($this->once())
            ->method('actualizarSaldo')
            ->with(
                $this->equalTo(1),
                $this->equalTo(799950.00)
            );

        $service = new ContabilidadService($contabRepo, $countryRepo, $logService, $db);

        $service->registrarGasto(3, 200000, 50, 1, 100);
    }
}