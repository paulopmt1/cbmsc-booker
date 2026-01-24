<?php

namespace App\Tests\Service;

use App\Constants\CbmscConstants;
use App\Entity\Bombeiro;
use App\Entity\Disponibilidade;
use App\Entity\Turno;
use App\Service\CalculadorDeAntiguidade;
use App\Service\CalculadorDePontos;
use PHPUnit\Framework\TestCase;

class DistribuicaoTurnosTest extends TestCase
{
    private CalculadorDeAntiguidade $calculadorDeAntiguidade;
    private CalculadorDePontos $calculador;

    protected function setUp(): void
    {
        // Mock do CalculadorDeAntiguidade
        $this->calculadorDeAntiguidade = $this->createMock(CalculadorDeAntiguidade::class);
        $this->calculadorDeAntiguidade->method('getAntiguidade')
            ->willReturn(50); // Retorna uma antiguidade padrão de 50

        $this->calculador = new CalculadorDePontos($this->calculadorDeAntiguidade, 10010010001);
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
        $dia = 1;

        // Cria 3 bombeiros com disponibilidades diferentes
        $bombeiro1 = new Bombeiro('Bombeiro 1', '11111111111', true);
        $bombeiro1->adicionarDisponibilidade(new Disponibilidade($dia, CbmscConstants::TURNO_INTEGRAL));

        $bombeiro2 = new Bombeiro('Bombeiro 2', '22222222222', true);
        $bombeiro2->adicionarDisponibilidade(new Disponibilidade($dia, CbmscConstants::TURNO_INTEGRAL));;

        $bombeiro3 = new Bombeiro('Bombeiro 3', '33333333333', true);
        $bombeiro3->adicionarDisponibilidade(new Disponibilidade($dia, CbmscConstants::TURNO_INTEGRAL));

        $this->calculador->adicionarBombeiro($bombeiro1);
        $this->calculador->adicionarBombeiro($bombeiro2);
        $this->calculador->adicionarBombeiro($bombeiro3);

        $resultado = $this->calculador->distribuirTurnosParaMes(60);

        $this->assertIsArray($resultado);
        $this->assertIsArray($resultado[$dia]['INTEGRAL']);
        $this->assertEquals(2, count($resultado[$dia]['INTEGRAL']));
        
        $this->assertIsArray($resultado[$dia]['DIURNO']);
        $this->assertEquals(1, count($resultado[$dia]['DIURNO']));
    }


    // TODO: Criar teste para novo método private obtemHorasDistribuidas
}

