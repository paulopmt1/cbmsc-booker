<?php

namespace App\Tests\FiremanBundle\Entity;

use App\Constants\CbmscConstants;
use App\FiremanBundle\Entity\FiremanEntity;
use App\AvailabilityBundle\Entity\AvailabilityEntity;
use App\ShiftBundle\Entity\ShiftEntity;
use PHPUnit\Framework\TestCase;

class FiremanEntityTest extends TestCase
{
    private function criaBombeiro(): FiremanEntity
    {
        $bombeiro = new FiremanEntity('Fulano', '12345678901', true);
        $bombeiro->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_DIURNO));
        $bombeiro->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_NOTURNO));
        $bombeiro->adicionarDisponibilidade(new AvailabilityEntity(3, CbmscConstants::TURNO_INTEGRAL));
        $bombeiro->adicionarDisponibilidade(new AvailabilityEntity(4, CbmscConstants::TURNO_INTEGRAL));

        return $bombeiro;
    }

    public function testConstrutorEValoresPadrao(): void
    {
        $bombeiro = new FiremanEntity('Fulano', '12345678901', true);

        $this->assertSame('Fulano', $bombeiro->getNome());
        $this->assertSame('12345678901', $bombeiro->getCpf());
        $this->assertTrue($bombeiro->getCarteiraAmbulancia());
        $this->assertSame(0, $bombeiro->getAntiguidade());
        $this->assertSame(0, $bombeiro->getPontuacao());
        $this->assertNull($bombeiro->getCidadeOrigem());
        $this->assertSame([], $bombeiro->getTurnosAdquiridos());
        $this->assertSame(0, $bombeiro->getDiasSolicitados());
    }

    public function testGetDisponibilidadePorDia(): void
    {
        $bombeiro = $this->criaBombeiro();

        $this->assertSame(CbmscConstants::TURNO_NOTURNO, $bombeiro->getDisponibilidade(2)->getTurno());
        $this->assertNull($bombeiro->getDisponibilidade(20));
    }

    public function testTemDisponibilidadeConsideraDiaETurno(): void
    {
        $bombeiro = $this->criaBombeiro();

        $this->assertTrue($bombeiro->temDisponibilidade(1, CbmscConstants::TURNO_DIURNO));
        $this->assertFalse($bombeiro->temDisponibilidade(1, CbmscConstants::TURNO_NOTURNO));
        $this->assertFalse($bombeiro->temDisponibilidade(20, CbmscConstants::TURNO_DIURNO));
    }

    public function testTemDisponibilidadeParaDia(): void
    {
        $bombeiro = $this->criaBombeiro();

        $this->assertTrue($bombeiro->temDisponibilidadeParaDia(3));
        $this->assertFalse($bombeiro->temDisponibilidadeParaDia(5));
    }

    public function testDiasSolicitadosContaDisponibilidades(): void
    {
        $this->assertSame(4, $this->criaBombeiro()->getDiasSolicitados());
    }

    /**
     * O nome 'BC CHEROBIN ' (com espaço no final) é tratado de forma especial para
     * garantir que ele sempre fique no topo da ordenação por percentual.
     */
    public function testDiasSolicitadosDoCherobinSaoArtificialmenteAltos(): void
    {
        $cherobin = new FiremanEntity('BC CHEROBIN ', '1', false);
        $cherobin->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_DIURNO));

        $this->assertSame(PHP_INT_MAX, $cherobin->getDiasSolicitados());
    }

    public function testRemoverDisponibilidade(): void
    {
        $bombeiro = $this->criaBombeiro();

        $this->assertTrue($bombeiro->removerDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_NOTURNO)));
        $this->assertFalse($bombeiro->temDisponibilidadeParaDia(2));
        $this->assertSame(3, $bombeiro->getDiasSolicitados());

        // Mesmo dia, turno diferente: não remove
        $this->assertFalse($bombeiro->removerDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_NOTURNO)));
        $this->assertSame(3, $bombeiro->getDiasSolicitados());
    }

    public function testSetDisponibilidadeSubstituiTodas(): void
    {
        $bombeiro = $this->criaBombeiro();
        $bombeiro->setDisponibilidade([new AvailabilityEntity(9, CbmscConstants::TURNO_DIURNO)]);

        $this->assertSame(1, $bombeiro->getDiasSolicitados());
        $this->assertTrue($bombeiro->temDisponibilidadeParaDia(9));
        $this->assertFalse($bombeiro->temDisponibilidadeParaDia(1));
    }

    public function testAdicionarERemoverTurnoAdquirido(): void
    {
        $bombeiro = $this->criaBombeiro();
        $bombeiro->adicionaTurnoAdquirido(new ShiftEntity(1, CbmscConstants::TURNO_DIURNO));
        $bombeiro->adicionaTurnoAdquirido(new ShiftEntity(3, CbmscConstants::TURNO_INTEGRAL));

        $this->assertCount(2, $bombeiro->getTurnosAdquiridos());

        // Remoção compara dia + turno, não a instância
        $bombeiro->removerTurnoAdquirido(new ShiftEntity(1, CbmscConstants::TURNO_DIURNO));

        $turnos = $bombeiro->getTurnosAdquiridos();
        $this->assertCount(1, $turnos);
        $this->assertSame(3, $turnos[0]->getDia(), 'Array deve ser reindexado após remoção');
    }

    public function testRemoverTurnoAdquiridoInexistenteNaoAltera(): void
    {
        $bombeiro = $this->criaBombeiro();
        $bombeiro->adicionaTurnoAdquirido(new ShiftEntity(1, CbmscConstants::TURNO_DIURNO));

        $bombeiro->removerTurnoAdquirido(new ShiftEntity(1, CbmscConstants::TURNO_NOTURNO));

        $this->assertCount(1, $bombeiro->getTurnosAdquiridos());
    }

    public function testPercentualDeServicosAceitos(): void
    {
        $bombeiro = $this->criaBombeiro(); // 4 dias solicitados
        $this->assertEquals(0, $bombeiro->getPercentualDeServicosAceitos());

        $bombeiro->adicionaTurnoAdquirido(new ShiftEntity(1, CbmscConstants::TURNO_DIURNO));
        $this->assertEquals(25, $bombeiro->getPercentualDeServicosAceitos());

        $bombeiro->adicionaTurnoAdquirido(new ShiftEntity(2, CbmscConstants::TURNO_NOTURNO));
        $bombeiro->adicionaTurnoAdquirido(new ShiftEntity(3, CbmscConstants::TURNO_INTEGRAL));
        $this->assertEquals(75, $bombeiro->getPercentualDeServicosAceitos());
    }

    public function testPercentualArredondaParaDuasCasas(): void
    {
        $bombeiro = new FiremanEntity('Fulano', '1', false);
        for ($dia = 1; $dia <= 3; $dia++) {
            $bombeiro->adicionarDisponibilidade(new AvailabilityEntity($dia, CbmscConstants::TURNO_DIURNO));
        }
        $bombeiro->adicionaTurnoAdquirido(new ShiftEntity(1, CbmscConstants::TURNO_DIURNO));

        $this->assertEquals(33.33, $bombeiro->getPercentualDeServicosAceitos());
    }

    public function testSetters(): void
    {
        $bombeiro = new FiremanEntity('Fulano', '1', false);
        $bombeiro->setAntiguidade(7);
        $bombeiro->setPontuacao(1234);
        $bombeiro->setCidadeOrigem(CbmscConstants::CIDADE_VIDEIRA);

        $this->assertSame(7, $bombeiro->getAntiguidade());
        $this->assertSame(1234, $bombeiro->getPontuacao());
        $this->assertSame(CbmscConstants::CIDADE_VIDEIRA, $bombeiro->getCidadeOrigem());
    }
}
