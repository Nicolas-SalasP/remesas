<?php

use PHPUnit\Framework\TestCase;
use App\Services\PricingService;
use App\Repositories\RateRepository;
use App\Repositories\CountryRepository;
use App\Services\NotificationService;

class PricingServiceTest extends TestCase
{
    public function testGetCurrentRateCalculaCorrectamente()
    {
        $rateRepo = $this->createMock(RateRepository::class);
        $countryRepo = $this->createMock(CountryRepository::class);
        $notifService = $this->createMock(NotificationService::class);

        $rateRepo->method('findCurrentRate')
            ->willReturn(['TasaID' => 1, 'ValorTasa' => 3.81]);

        $service = new PricingService($rateRepo, $countryRepo, $notifService);

        $resultado = $service->getCurrentRate(1, 2, 10000);

        $this->assertIsArray($resultado);
        $this->assertEquals(3.81, $resultado['ValorTasa']);
    }

    public function testErrorSiOrigenYDestinoSonIguales()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("no pueden ser iguales");

        $rateRepo = $this->createMock(RateRepository::class);
        $countryRepo = $this->createMock(CountryRepository::class);
        $notifService = $this->createMock(NotificationService::class);

        $service = new PricingService($rateRepo, $countryRepo, $notifService);

        $service->getCurrentRate(1, 1, 10000);
    }
}