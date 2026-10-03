<?php

namespace App\Tests\FiremanBundle\Service;

use App\FiremanBundle\Entity\FiremanEntity;
use App\FiremanBundle\Service\SeniorityCalculator;
use PHPUnit\Framework\TestCase;

class SeniorityCalculatorBehaviorTest extends TestCase
{
    private SeniorityCalculator $calculador;

    protected function setUp(): void
    {
        $this->calculador = new SeniorityCalculator();
    }

    public function testSemDadosRetornaZero(): void
    {
        $this->assertSame(0, $this->calculador->getAntiguidade(new FiremanEntity('A', '111', false)));
    }

    /**
     * Na planilha, posição 1 = mais antigo. Internamente, maior valor = mais prioridade.
     */
    public function testNormalizaPosicaoDeAntiguidade(): void
    {
        // Formato vindo do Google Sheets (range A2:B): [CPF, posição], tudo string
        $this->calculador->setAntiguidadeData([
            ['111', '1'],
            ['222', '10'],
            ['333', '99'],
        ]);

        $this->assertSame(99, $this->calculador->getAntiguidade(new FiremanEntity('A', '111', false)));
        $this->assertSame(90, $this->calculador->getAntiguidade(new FiremanEntity('B', '222', false)));
        $this->assertSame(1, $this->calculador->getAntiguidade(new FiremanEntity('C', '333', false)));
    }

    public function testMaisAntigoRecebeMaisPontos(): void
    {
        $this->calculador->setAntiguidadeData([['111', '1'], ['222', '2']]);

        $maisAntigo = $this->calculador->getAntiguidade(new FiremanEntity('A', '111', false));
        $maisNovo = $this->calculador->getAntiguidade(new FiremanEntity('B', '222', false));

        $this->assertGreaterThan($maisNovo, $maisAntigo);
    }

    public function testCpfNaoEncontradoRetornaZero(): void
    {
        $this->calculador->setAntiguidadeData([['111', '1']]);

        $this->assertSame(0, $this->calculador->getAntiguidade(new FiremanEntity('X', '999', false)));
    }

    public function testIgnoraLinhasIncompletas(): void
    {
        // O Google Sheets omite células vazias no fim da linha
        $this->calculador->setAntiguidadeData([
            [],
            ['111'],
            ['222', '5'],
        ]);

        $this->assertSame(0, $this->calculador->getAntiguidade(new FiremanEntity('A', '111', false)));
        $this->assertSame(95, $this->calculador->getAntiguidade(new FiremanEntity('B', '222', false)));
    }

    public function testSetAntiguidadeDataSubstituiDadosAnteriores(): void
    {
        $this->calculador->setAntiguidadeData([['111', '1']]);
        $this->calculador->setAntiguidadeData([['111', '50']]);

        $this->assertSame(50, $this->calculador->getAntiguidade(new FiremanEntity('A', '111', false)));
    }
}
