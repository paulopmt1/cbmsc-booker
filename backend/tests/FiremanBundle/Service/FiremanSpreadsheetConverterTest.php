<?php

namespace App\Tests\FiremanBundle\Service;

use App\Constants\CbmscConstants;
use App\FiremanBundle\Entity\FiremanEntity;
use App\AvailabilityBundle\Entity\AvailabilityEntity;
use App\FiremanBundle\Service\FiremanSpreadsheetConverter;
use PHPUnit\Framework\TestCase;

class FiremanSpreadsheetConverterTest extends TestCase
{
    private FiremanSpreadsheetConverter $conversor;

    protected function setUp(): void
    {
        $this->conversor = new FiremanSpreadsheetConverter();
    }

    /**
     * Monta uma linha no formato da planilha de "escolha de horários" (Google Forms):
     * A = data da resposta, B = nome, C = CPF, D = carteira de ambulância, E..AI = dias 1..31
     *
     * @param array<int, string> $turnosPorDia [dia => 'Integral'|'Diurno'|'Noturno'|...]
     */
    public static function linhaPlanilha(string $nome, string $cpf, string $carteira, array $turnosPorDia = []): array
    {
        $linha = ['01/08/2025 10:00:00', $nome, $cpf, $carteira];

        if (empty($turnosPorDia)) {
            return $linha;
        }

        // O Google Sheets omite as células vazias no fim da linha
        $ultimoDia = max(array_keys($turnosPorDia));
        for ($dia = 1; $dia <= $ultimoDia; $dia++) {
            $linha[] = $turnosPorDia[$dia] ?? '';
        }

        return $linha;
    }

    // ---------------------------------------------------------------
    // Planilha de respostas -> Bombeiros
    // ---------------------------------------------------------------

    public function testConverteLinhaSimples(): void
    {
        $bombeiros = $this->conversor->convertePlanilhaParaObjetosDeBombeiros([
            self::linhaPlanilha('BC Fulano', '12345678901', 'Sim', [1 => 'Integral', 2 => 'Diurno', 31 => 'Noturno']),
        ]);

        $this->assertCount(1, $bombeiros);
        $bombeiro = $bombeiros[0];

        $this->assertSame('BC Fulano', $bombeiro->getNome());
        $this->assertSame('12345678901', $bombeiro->getCpf());
        $this->assertTrue($bombeiro->getCarteiraAmbulancia());
        $this->assertSame(3, $bombeiro->getDiasSolicitados());
        $this->assertSame(CbmscConstants::TURNO_INTEGRAL, $bombeiro->getDisponibilidade(1)->getTurno());
        $this->assertSame(CbmscConstants::TURNO_DIURNO, $bombeiro->getDisponibilidade(2)->getTurno());
        $this->assertSame(CbmscConstants::TURNO_NOTURNO, $bombeiro->getDisponibilidade(31)->getTurno());
        $this->assertNull($bombeiro->getDisponibilidade(3));
    }

    public function testColunaDoDiaUmEhAColunaE(): void
    {
        // Índice 4 (coluna E) é o dia 1; índice 34 (coluna AI) é o dia 31
        $linha = array_fill(0, 35, '');
        $linha[1] = 'Nome';
        $linha[2] = '1';
        $linha[3] = 'Não';
        $linha[4] = 'Diurno';
        $linha[34] = 'Noturno';

        $bombeiro = $this->conversor->convertePlanilhaParaObjetosDeBombeiros([$linha])[0];

        $this->assertSame(2, $bombeiro->getDiasSolicitados());
        $this->assertSame(CbmscConstants::TURNO_DIURNO, $bombeiro->getDisponibilidade(1)->getTurno());
        $this->assertSame(CbmscConstants::TURNO_NOTURNO, $bombeiro->getDisponibilidade(31)->getTurno());
    }

    public function testCarteiraDeAmbulanciaSomenteComSimExato(): void
    {
        $bombeiros = $this->conversor->convertePlanilhaParaObjetosDeBombeiros([
            self::linhaPlanilha('A', '1', 'Sim', [1 => 'Diurno']),
            self::linhaPlanilha('B', '2', 'Não', [1 => 'Diurno']),
            self::linhaPlanilha('C', '3', '', [1 => 'Diurno']),
        ]);

        $this->assertTrue($bombeiros[0]->getCarteiraAmbulancia());
        $this->assertFalse($bombeiros[1]->getCarteiraAmbulancia());
        $this->assertFalse($bombeiros[2]->getCarteiraAmbulancia());
    }

    public function testTurnoEhCaseInsensitive(): void
    {
        $bombeiro = $this->conversor->convertePlanilhaParaObjetosDeBombeiros([
            self::linhaPlanilha('A', '1', 'Não', [1 => 'integral', 2 => 'DIURNO', 3 => 'NoTuRnO']),
        ])[0];

        $this->assertSame(CbmscConstants::TURNO_INTEGRAL, $bombeiro->getDisponibilidade(1)->getTurno());
        $this->assertSame(CbmscConstants::TURNO_DIURNO, $bombeiro->getDisponibilidade(2)->getTurno());
        $this->assertSame(CbmscConstants::TURNO_NOTURNO, $bombeiro->getDisponibilidade(3)->getTurno());
    }

    public function testIgnoraValoresDeTurnoInvalidos(): void
    {
        $bombeiro = $this->conversor->convertePlanilhaParaObjetosDeBombeiros([
            self::linhaPlanilha('A', '1', 'Não', [1 => 'Folga', 2 => 'Diurno', 3 => 'x']),
        ])[0];

        $this->assertSame(1, $bombeiro->getDiasSolicitados());
        $this->assertTrue($bombeiro->temDisponibilidadeParaDia(2));
    }

    public function testIgnoraLinhasSemNome(): void
    {
        $bombeiros = $this->conversor->convertePlanilhaParaObjetosDeBombeiros([
            self::linhaPlanilha('', '1', 'Não', [1 => 'Diurno']),
            self::linhaPlanilha('B', '2', 'Não', [1 => 'Diurno']),
        ]);

        $this->assertCount(1, $bombeiros);
        $this->assertSame('B', $bombeiros[0]->getNome());
    }

    public function testBombeiroSemDiasAindaEhIncluido(): void
    {
        $bombeiros = $this->conversor->convertePlanilhaParaObjetosDeBombeiros([
            self::linhaPlanilha('A', '1', 'Não'),
        ]);

        $this->assertCount(1, $bombeiros);
        $this->assertSame(0, $bombeiros[0]->getDiasSolicitados());
    }

    public function testMantemOrdemDasLinhas(): void
    {
        $bombeiros = $this->conversor->convertePlanilhaParaObjetosDeBombeiros([
            self::linhaPlanilha('C', '3', 'Não', [1 => 'Diurno']),
            self::linhaPlanilha('A', '1', 'Não', [1 => 'Diurno']),
            self::linhaPlanilha('B', '2', 'Não', [1 => 'Diurno']),
        ]);

        $this->assertSame(['C', 'A', 'B'], array_map(fn (FiremanEntity $b) => $b->getNome(), $bombeiros));
    }

    public function testPlanilhaVaziaRetornaArrayVazio(): void
    {
        $this->assertSame([], $this->conversor->convertePlanilhaParaObjetosDeBombeiros([]));
    }

    // ---------------------------------------------------------------
    // Bombeiros -> PME Preliminar (conversão simples)
    // ---------------------------------------------------------------

    public function testConverterBombeirosParaPlanilhaFormatoDaLinha(): void
    {
        $bombeiro = new FiremanEntity('BC Fulano', '123', true);
        $bombeiro->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $bombeiro->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_DIURNO));
        $bombeiro->adicionarDisponibilidade(new AvailabilityEntity(31, CbmscConstants::TURNO_NOTURNO));

        $planilha = $this->conversor->converterBombeirosParaPlanilha([$bombeiro]);

        $this->assertCount(1, $planilha);
        $linha = $planilha[0];

        // A = nome, B = CPF, C = carteira, D..AH = dias 1..31 (34 colunas)
        $this->assertCount(34, $linha);
        $this->assertSame(range(0, 33), array_keys($linha), 'Chaves devem ser sequenciais para a API do Sheets');
        $this->assertSame('BC Fulano', $linha[0]);
        $this->assertSame('123', $linha[1]);
        $this->assertTrue($linha[2]);
        $this->assertSame('I', $linha[3]);
        $this->assertSame('D', $linha[4]);
        $this->assertSame('', $linha[5]);
        $this->assertSame('N', $linha[33]);
    }

    public function testConverterBombeirosParaPlanilhaSemDisponibilidade(): void
    {
        $linha = $this->conversor->converterBombeirosParaPlanilha([new FiremanEntity('A', '1', false)])[0];

        $this->assertFalse($linha[2]);
        $this->assertSame(array_fill(3, 31, ''), array_slice($linha, 3, null, true));
    }

    public function testConverterBombeirosParaPlanilhaMantemOrdem(): void
    {
        $planilha = $this->conversor->converterBombeirosParaPlanilha([
            new FiremanEntity('B', '2', false),
            new FiremanEntity('A', '1', false),
        ]);

        $this->assertSame(['B', 'A'], array_column($planilha, 0));
    }

    // ---------------------------------------------------------------
    // Resultado do algoritmo -> PME Preliminar
    // ---------------------------------------------------------------

    public function testConverterTurnosMarcaSomenteDiasSelecionados(): void
    {
        $selecionado = new FiremanEntity('Selecionado', '1', false);
        $selecionado->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));
        $selecionado->adicionarDisponibilidade(new AvailabilityEntity(2, CbmscConstants::TURNO_DIURNO));

        $naoSelecionado = new FiremanEntity('Nao selecionado', '2', false);
        $naoSelecionado->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_NOTURNO));

        $todosOsTurnos = [
            1 => [CbmscConstants::TURNO_INTEGRAL => [$selecionado]],
            2 => [CbmscConstants::TURNO_DIURNO => [$selecionado]],
        ];

        $planilha = $this->conversor->converterTurnosDisponibilidadeParaPlanilha($todosOsTurnos, [$selecionado, $naoSelecionado]);

        $this->assertCount(2, $planilha);
        $this->assertCount(34, $planilha[0]);

        $this->assertSame('Selecionado', $planilha[0][0]);
        $this->assertSame('I', $planilha[0][3]);
        $this->assertSame('D', $planilha[0][4]);
        $this->assertSame('', $planilha[0][5]);

        $this->assertSame('Nao selecionado', $planilha[1][0]);
        $this->assertSame('', $planilha[1][3], 'Disponível mas não selecionado deve ficar em branco');
    }

    public function testConverterTurnosIgnoraDiasAusentes(): void
    {
        $bombeiro = new FiremanEntity('A', '1', false);
        $bombeiro->adicionarDisponibilidade(new AvailabilityEntity(5, CbmscConstants::TURNO_DIURNO));

        // Nenhum dia no resultado: não pode gerar warning de chave inexistente
        $planilha = $this->conversor->converterTurnosDisponibilidadeParaPlanilha([], [$bombeiro]);

        $this->assertSame(array_fill(3, 31, ''), array_slice($planilha[0], 3, null, true));
    }

    /**
     * Comportamento atual: a letra exibida vem da disponibilidade solicitada, não do turno
     * efetivamente alocado. Um INTEGRAL decomposto em DIURNO aparece como 'I' na planilha.
     */
    public function testConverterTurnosUsaLetraDaDisponibilidadeSolicitada(): void
    {
        $bombeiro = new FiremanEntity('A', '1', false);
        $bombeiro->adicionarDisponibilidade(new AvailabilityEntity(1, CbmscConstants::TURNO_INTEGRAL));

        $todosOsTurnos = [1 => [CbmscConstants::TURNO_DIURNO => [$bombeiro]]];

        $planilha = $this->conversor->converterTurnosDisponibilidadeParaPlanilha($todosOsTurnos, [$bombeiro]);

        $this->assertSame('I', $planilha[0][3]);
    }
}
