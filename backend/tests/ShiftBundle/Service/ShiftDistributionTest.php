<?php

namespace App\Tests\ShiftBundle\Service;

use App\Constants\CbmscConstants;
use App\FiremanBundle\Entity\FiremanEntity;
use App\AvailabilityBundle\Entity\AvailabilityEntity;
use App\ShiftBundle\Entity\ShiftEntity;
use App\FiremanBundle\Service\SeniorityCalculator;
use App\ShiftBundle\Service\ShiftAllocator;
use PHPUnit\Framework\TestCase;

class ShiftDistributionTest extends TestCase
{
    private SeniorityCalculator $seniorityCalculator;
    private ShiftAllocator $allocator;

    protected function setUp(): void
    {
        // Mock do SeniorityCalculator
        $this->seniorityCalculator = $this->createMock(SeniorityCalculator::class);
        $this->seniorityCalculator->method('getAntiguidade')
            ->willReturn(50); // Retorna uma antiguidade padrão de 50

        $this->allocator = new ShiftAllocator($this->seniorityCalculator, 10010010001);
    }

    /**
     * Caso algum dia tenhamos 3 bombeiros para fazer serviço integral, iremos
     * decompor um serviço integral em 1 diurno
     * 
     * Isso é importante, pois permite asseguramos que teremos todas as vagas 
     * preenchidas.
     * 
     * TODO: Talvez precisamos suportar casos onde o bombeiro pode apenas integral.
     * Por exemplo, pode não ser viável um serviço não integral para um bombeiro que vem de outra cidade
     */
    public function testDecompoeServicoIntegralEmDiurno(): void
    {
        $day = 1;

        // Cria 3 bombeiros com disponibilidades diferentes
        $firefighter1 = new FiremanEntity('Bombeiro 1', '11111111111', true);
        $firefighter1->adicionarDisponibilidade(new AvailabilityEntity($day, CbmscConstants::TURNO_INTEGRAL));

        $firefighter2 = new FiremanEntity('Bombeiro 2', '22222222222', true);
        $firefighter2->adicionarDisponibilidade(new AvailabilityEntity($day, CbmscConstants::TURNO_INTEGRAL));;

        $firefighter3 = new FiremanEntity('Bombeiro 3', '33333333333', true);
        $firefighter3->adicionarDisponibilidade(new AvailabilityEntity($day, CbmscConstants::TURNO_INTEGRAL));

        $this->allocator->adicionarBombeiro($firefighter1);
        $this->allocator->adicionarBombeiro($firefighter2);
        $this->allocator->adicionarBombeiro($firefighter3);

        $result = $this->allocator->distribuirTurnosParaMes(60);

        $this->assertIsArray($result);
        $this->assertIsArray($result[$day]['INTEGRAL']);
        $this->assertEquals(2, count($result[$day]['INTEGRAL']));
        
        $this->assertIsArray($result[$day]['DIURNO']);
        $this->assertEquals(1, count($result[$day]['DIURNO']));
    }


    // TODO: Criar teste para novo método private obtemHorasDistribuidas
}
