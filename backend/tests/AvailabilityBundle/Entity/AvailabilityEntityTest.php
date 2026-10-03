<?php

namespace App\Tests\AvailabilityBundle\Entity;

use App\Constants\CbmscConstants;
use App\AvailabilityBundle\Entity\AvailabilityEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AvailabilityEntityTest extends TestCase
{
    public function testConstrutorDefineDiaETurno(): void
    {
        $disponibilidade = new AvailabilityEntity(15, CbmscConstants::TURNO_NOTURNO);

        $this->assertSame(15, $disponibilidade->getDia());
        $this->assertSame(CbmscConstants::TURNO_NOTURNO, $disponibilidade->getTurno());
        $this->assertSame((int) date('n'), $disponibilidade->getMes());
    }

    public static function diasValidosProvider(): array
    {
        return [[1], [15], [31]];
    }

    #[DataProvider('diasValidosProvider')]
    public function testAceitaDiasValidos(int $dia): void
    {
        $disponibilidade = new AvailabilityEntity($dia, CbmscConstants::TURNO_DIURNO);

        $this->assertSame($dia, $disponibilidade->getDia());
    }

    public static function diasInvalidosProvider(): array
    {
        return [[0], [32], [-1]];
    }

    #[DataProvider('diasInvalidosProvider')]
    public function testRejeitaDiasInvalidos(int $dia): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AvailabilityEntity($dia, CbmscConstants::TURNO_DIURNO);
    }

    public function testRejeitaTurnoInvalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // A planilha envia em maiúsculas; o conversor é responsável pelo strtoupper
        new AvailabilityEntity(1, 'diurno');
    }

    public function testSetMesValidaIntervalo(): void
    {
        $disponibilidade = new AvailabilityEntity(1, CbmscConstants::TURNO_DIURNO);
        $disponibilidade->setMes(12);
        $this->assertSame(12, $disponibilidade->getMes());

        $this->expectException(\InvalidArgumentException::class);
        $disponibilidade->setMes(13);
    }

    public function testEquals(): void
    {
        $a = new AvailabilityEntity(5, CbmscConstants::TURNO_INTEGRAL);
        $b = new AvailabilityEntity(5, CbmscConstants::TURNO_INTEGRAL);
        $outroDia = new AvailabilityEntity(6, CbmscConstants::TURNO_INTEGRAL);
        $outroTurno = new AvailabilityEntity(5, CbmscConstants::TURNO_DIURNO);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($outroDia));
        $this->assertFalse($a->equals($outroTurno));
    }

    public function testToString(): void
    {
        $disponibilidade = new AvailabilityEntity(3, CbmscConstants::TURNO_DIURNO);

        $this->assertSame(sprintf('Dia: 3, Mês: %d, Ano: %d, Turno: DIURNO', $disponibilidade->getMes(), $disponibilidade->getAno()), (string) $disponibilidade);
    }
}
