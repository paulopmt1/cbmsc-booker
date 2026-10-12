<?php

namespace App\Tests\Entity;

use App\Constants\CbmscConstants;
use App\Entity\Turno;
use PHPUnit\Framework\TestCase;

class TurnoTest extends TestCase
{
    public function testConstrutorComValoresPadrao(): void
    {
        $turno = new Turno(10, CbmscConstants::TURNO_INTEGRAL);

        $this->assertSame(10, $turno->getDia());
        $this->assertSame(CbmscConstants::TURNO_INTEGRAL, $turno->getTurno());
        $this->assertFalse($turno->getETurnoIntegralDecomposto());
    }

    public function testTurnoIntegralDecomposto(): void
    {
        $turno = new Turno(10, CbmscConstants::TURNO_DIURNO, true);

        $this->assertTrue($turno->getETurnoIntegralDecomposto());
    }

    public function testAceitaTodosOsTurnosValidos(): void
    {
        foreach (CbmscConstants::getTurnosValidos() as $valor) {
            $this->assertSame($valor, (new Turno(1, $valor))->getTurno());
        }
    }

    public function testRejeitaTurnoInvalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Turno inválido: "MANHA"');

        new Turno(1, 'MANHA');
    }
}
