<?php

namespace App\Tests\ShiftBundle\Service;

use App\Constants\CbmscConstants;
use App\FiremanBundle\Entity\FiremanEntity;
use App\ShiftBundle\Entity\ShiftEntity;
use App\FiremanBundle\Service\SeniorityCalculator;
use App\ShiftBundle\Service\ShiftAllocator;
use App\FiremanBundle\Service\FiremanSpreadsheetConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Testes de ponta a ponta do fluxo principal, sem Google Sheets:
 *
 *   planilha de respostas -> Bombeiros -> distribuição do mês -> planilha PME Preliminar
 *
 * Em vez de fixar a escala exata (que muda a cada ajuste do algoritmo), estes testes
 * verificam regras de negócio que devem valer para QUALQUER mês gerado.
 */
class MonthlyDistributionInvariantsTest extends TestCase
{
    private const CPF_QUERUBIN = 1003;

    /**
     * Gera, de forma determinística, as linhas de uma planilha de respostas realista.
     */
    private static function geraPlanilhaDeRespostas(int $seed, int $quantidadeBombeiros): array
    {
        mt_srand($seed);
        $turnos = ['Integral', 'Diurno', 'Noturno'];
        $linhas = [];

        for ($i = 0; $i < $quantidadeBombeiros; $i++) {
            $linha = ['01/08/2025 10:00:00', "BC {$i}", strval(1000 + $i), mt_rand(0, 1) ? 'Sim' : 'Não'];
            for ($dia = 1; $dia <= 31; $dia++) {
                // ~35% de chance de o bombeiro se oferecer para cada dia
                $linha[] = mt_rand(0, 99) < 35 ? $turnos[mt_rand(0, 2)] : '';
            }
            $linhas[] = $linha;
        }

        mt_srand();

        return $linhas;
    }

    /**
     * Executa o fluxo completo e retorna [bombeiros, todosOsTurnos, planilhaAlgoritmo, planilhaSimples]
     */
    private static function executaFluxo(int $seed, int $quantidadeBombeiros, int $horasPorDia, ?array $diasMotorista): array
    {
        $conversor = new FiremanSpreadsheetConverter();
        $bombeiros = $conversor->convertePlanilhaParaObjetosDeBombeiros(
            self::geraPlanilhaDeRespostas($seed, $quantidadeBombeiros)
        );

        $antiguidade = new SeniorityCalculator();
        $antiguidade->setAntiguidadeData(array_map(
            fn (int $i) => [strval(1000 + $i), strval($i + 1)],
            range(0, $quantidadeBombeiros - 1)
        ));

        $calculador = new ShiftAllocator($antiguidade, self::CPF_QUERUBIN);
        foreach ($bombeiros as $bombeiro) {
            $calculador->adicionarBombeiro($bombeiro);
        }

        $todosOsTurnos = $calculador->distribuirTurnosParaMes($horasPorDia, $diasMotorista);

        return [
            $bombeiros,
            $todosOsTurnos,
            $conversor->converterTurnosDisponibilidadeParaPlanilha($todosOsTurnos, $bombeiros),
            $conversor->converterBombeirosParaPlanilha($bombeiros),
        ];
    }

    public static function cenariosProvider(): array
    {
        return [
            'mês típico, 2.5 cotas'                 => [1, 25, 60, null],
            'muitos bombeiros, dias com motorista'  => [2, 40, 60, [3, 4, 10]],
            'poucos bombeiros (falta de efetivo)'   => [3, 8, 60, null],
            'efetivo grande, semana com motorista'  => [4, 60, 60, [1, 2, 3, 4, 5]],
            '1.5 cotas por dia'                     => [5, 25, 36, null],
            '3 cotas por dia'                       => [6, 25, 72, [7]],
        ];
    }

    private static function horasDoTurno(string $turno): int
    {
        return $turno === CbmscConstants::TURNO_INTEGRAL ? 24 : 12;
    }

    #[DataProvider('cenariosProvider')]
    public function testNenhumDiaUltrapassaAsHorasContratadas(int $seed, int $n, int $horasPorDia, ?array $diasMotorista): void
    {
        [, $todosOsTurnos] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);

        foreach ($todosOsTurnos as $dia => $turnos) {
            $horas = 0;
            foreach ($turnos as $turno => $bombeiros) {
                $horas += self::horasDoTurno($turno) * count($bombeiros);
            }
            $this->assertLessThanOrEqual($horasPorDia, $horas, "Dia {$dia} ultrapassou {$horasPorDia}h");
        }
    }

    #[DataProvider('cenariosProvider')]
    public function testLimiteDeCotasIntegraisPorDia(int $seed, int $n, int $horasPorDia, ?array $diasMotorista): void
    {
        [, $todosOsTurnos] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);
        $limite = intdiv($horasPorDia, 24);

        foreach ($todosOsTurnos as $dia => $turnos) {
            $this->assertLessThanOrEqual(
                $limite,
                count($turnos[CbmscConstants::TURNO_INTEGRAL] ?? []),
                "Dia {$dia} tem mais integrais do que cabem em {$horasPorDia}h"
            );
        }
    }

    #[DataProvider('cenariosProvider')]
    public function testBombeiroNaoEhEscaladoDuasVezesNoMesmoDia(int $seed, int $n, int $horasPorDia, ?array $diasMotorista): void
    {
        [, $todosOsTurnos] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);

        foreach ($todosOsTurnos as $dia => $turnos) {
            $nomes = [];
            foreach ($turnos as $bombeiros) {
                foreach ($bombeiros as $bombeiro) {
                    $nomes[] = $bombeiro->getNome();
                }
            }
            $this->assertSame(count($nomes), count(array_unique($nomes)), "Bombeiro duplicado no dia {$dia}");
        }
    }

    #[DataProvider('cenariosProvider')]
    public function testEscalaRespeitaDisponibilidadeInformada(int $seed, int $n, int $horasPorDia, ?array $diasMotorista): void
    {
        [, $todosOsTurnos] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);

        foreach ($todosOsTurnos as $dia => $turnos) {
            foreach ($turnos as $turno => $bombeiros) {
                /** @var FiremanEntity $bombeiro */
                foreach ($bombeiros as $bombeiro) {
                    $disponibilidade = $bombeiro->getDisponibilidade($dia);
                    $this->assertNotNull($disponibilidade, "{$bombeiro->getNome()} escalado no dia {$dia} sem disponibilidade");

                    // Mesmo turno solicitado, ou um INTEGRAL decomposto em DIURNO/NOTURNO
                    $this->assertTrue(
                        $disponibilidade->getTurno() === $turno || $disponibilidade->getTurno() === CbmscConstants::TURNO_INTEGRAL,
                        "{$bombeiro->getNome()} pediu {$disponibilidade->getTurno()} e recebeu {$turno} no dia {$dia}"
                    );
                }
            }
        }
    }

    #[DataProvider('cenariosProvider')]
    public function testNinguemRecebeMaisQueOLimiteDeIntegraisNoMes(int $seed, int $n, int $horasPorDia, ?array $diasMotorista): void
    {
        [$bombeiros] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);

        foreach ($bombeiros as $bombeiro) {
            $integrais = array_filter(
                $bombeiro->getTurnosAdquiridos(),
                fn (ShiftEntity $t) => $t->getTurno() === CbmscConstants::TURNO_INTEGRAL
            );
            $this->assertLessThanOrEqual(CbmscConstants::COTAS_INTEGRAIS_POR_MES, count($integrais), $bombeiro->getNome());
        }
    }

    #[DataProvider('cenariosProvider')]
    public function testTodoBombeiroQueSolicitouRecebePeloMenosUmTurno(int $seed, int $n, int $horasPorDia, ?array $diasMotorista): void
    {
        [$bombeiros] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);

        foreach ($bombeiros as $bombeiro) {
            if ($bombeiro->getDiasSolicitados() > 0) {
                $this->assertNotEmpty($bombeiro->getTurnosAdquiridos(), "{$bombeiro->getNome()} ficou sem turno no mês");
            }
        }
    }

    /**
     * O resultado (todosOsTurnos) e o estado de cada bombeiro (turnosAdquiridos) são mantidos
     * em paralelo pelo algoritmo. Eles precisam concordar, senão a planilha sai errada.
     */
    #[DataProvider('cenariosProvider')]
    public function testTurnosAdquiridosConsistentesComEscala(int $seed, int $n, int $horasPorDia, ?array $diasMotorista): void
    {
        [$bombeiros, $todosOsTurnos] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);

        $totalNaEscala = 0;
        foreach ($todosOsTurnos as $dia => $turnos) {
            foreach ($turnos as $turno => $escalados) {
                foreach ($escalados as $bombeiro) {
                    $totalNaEscala++;
                    $encontrado = array_filter(
                        $bombeiro->getTurnosAdquiridos(),
                        fn (ShiftEntity $t) => $t->getDia() === $dia && $t->getTurno() === $turno
                    );
                    $this->assertCount(1, $encontrado, "{$bombeiro->getNome()} está na escala do dia {$dia} ({$turno}) mas não tem o turno registrado");
                }
            }
        }

        $totalAdquirido = 0;
        foreach ($bombeiros as $bombeiro) {
            foreach ($bombeiro->getTurnosAdquiridos() as $t) {
                $totalAdquirido++;
                $this->assertContains(
                    $bombeiro,
                    $todosOsTurnos[$t->getDia()][$t->getTurno()] ?? [],
                    "{$bombeiro->getNome()} tem turno registrado no dia {$t->getDia()} que não está na escala"
                );
            }
        }

        $this->assertSame($totalNaEscala, $totalAdquirido);
    }

    public static function cenariosComMotoristaProvider(): array
    {
        return array_filter(self::cenariosProvider(), fn (array $cenario) => $cenario[3] !== null);
    }

    #[DataProvider('cenariosComMotoristaProvider')]
    public function testDiasComMotoristaRecebemMotoristaQuandoHaDisponivel(int $seed, int $n, int $horasPorDia, array $diasMotorista): void
    {
        [$bombeiros, $todosOsTurnos] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);

        foreach ($diasMotorista as $dia) {
            $motoristaDisponivel = array_filter(
                $bombeiros,
                fn (FiremanEntity $b) => $b->getCarteiraAmbulancia() && $b->temDisponibilidadeParaDia($dia)
            );
            if (empty($motoristaDisponivel)) {
                continue;
            }

            $motoristasEscalados = 0;
            foreach ($todosOsTurnos[$dia] ?? [] as $escalados) {
                foreach ($escalados as $bombeiro) {
                    $motoristasEscalados += $bombeiro->getCarteiraAmbulancia() ? 1 : 0;
                }
            }
            $this->assertGreaterThan(0, $motoristasEscalados, "Dia {$dia} precisava de motorista e havia disponível");
        }
    }

    #[DataProvider('cenariosProvider')]
    public function testDistribuicaoEhDeterministica(int $seed, int $n, int $horasPorDia, ?array $diasMotorista): void
    {
        [, , $primeira] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);
        [, , $segunda] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);

        $this->assertSame($primeira, $segunda);
    }

    #[DataProvider('cenariosProvider')]
    public function testPlanilhaDoAlgoritmoRefleteAEscala(int $seed, int $n, int $horasPorDia, ?array $diasMotorista): void
    {
        [$bombeiros, , $planilha] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);

        $this->assertCount(count($bombeiros), $planilha);

        foreach ($planilha as $i => $linha) {
            $bombeiro = $bombeiros[$i];
            $this->assertCount(34, $linha);
            $this->assertSame($bombeiro->getNome(), $linha[0]);
            $this->assertSame($bombeiro->getCpf(), $linha[1]);
            $this->assertSame($bombeiro->getCarteiraAmbulancia(), $linha[2]);

            $diasEscalados = array_map(fn (ShiftEntity $t) => $t->getDia(), $bombeiro->getTurnosAdquiridos());

            for ($dia = 1; $dia <= 31; $dia++) {
                $celula = $linha[$dia + 2];
                if (in_array($dia, $diasEscalados, true)) {
                    $this->assertContains($celula, ['I', 'D', 'N'], "{$bombeiro->getNome()} dia {$dia}");
                } else {
                    $this->assertSame('', $celula, "{$bombeiro->getNome()} não foi escalado no dia {$dia}");
                }
            }
        }
    }

    #[DataProvider('cenariosProvider')]
    public function testPlanilhaSimplesRefleteTodasAsDisponibilidades(int $seed, int $n, int $horasPorDia, ?array $diasMotorista): void
    {
        [$bombeiros, , , $planilha] = self::executaFluxo($seed, $n, $horasPorDia, $diasMotorista);

        $letras = [
            CbmscConstants::TURNO_INTEGRAL => 'I',
            CbmscConstants::TURNO_DIURNO => 'D',
            CbmscConstants::TURNO_NOTURNO => 'N',
        ];

        foreach ($planilha as $i => $linha) {
            for ($dia = 1; $dia <= 31; $dia++) {
                $disponibilidade = $bombeiros[$i]->getDisponibilidade($dia);
                $esperado = $disponibilidade ? $letras[$disponibilidade->getTurno()] : '';
                $this->assertSame($esperado, $linha[$dia + 2]);
            }
        }
    }
}
