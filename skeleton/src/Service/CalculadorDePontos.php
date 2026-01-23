<?php

namespace App\Service;

use App\Constants\CbmscConstants;
use App\Entity\Bombeiro;
use App\Service\CalculadorDeAntiguidade;
use App\Entity\Turno;

class CalculadorDePontos {

    
    public function __construct(
        private readonly CalculadorDeAntiguidade $calculadorDeAntiguidade
    ) {
    }

    /**
     * Aqui definimos quantos BCs por turno podemos ter
     * Esse termo é chamado de cotas, hoje suportamos 2.5 cotas de 24h, ou seja, 60h por dia
     * 
     * Uma cota integral é 24h, uma meia cota é 12h.
     * Atualmente temos 2.5 cotas totais (5 meias cotas) que podem ser distribuídas:
     *      - 2 integral + 1 meia cota (24h * 2 + 12h = 60h)
     *      - 1 integral + 3 meias cotas (24h + 12h * 3 = 60h)
     *      - 5 meias cotas (12h * 5 = 60h)
     */


    /**
     * Array de bombeiros que serão utilizados para o serviço do mês
     * @var $bombeiros array<App\Entity\Bombeiro>
     */
    private $bombeiros = [];

    /**
     * Array de dias que precisam de motorista adicional
     * @var array|null
     */
    private ?array $diasQuePrecisaMotoristaAdicional = null;

    /**
     * Usada para debugging mostrando quais são todos os bombeiros disponíveis para cada dia do mês.
     */
    public function computarTodosOsTurnos() {
        $todosOsTurnos = [];

        // Para cada dia do mês, computamos os turnos dos bombeiros
        for ($dia = 1; $dia <= 31; $dia++) {
            $turnos_do_dia = $this->computarTurnosDoDia($dia);

            // Adiciona o dia e os turnos ao array de turnos do mês
            $todosOsTurnos[$dia] = [
                'dia' => $dia,
                'turnos' => $turnos_do_dia
            ];
        }

        return $todosOsTurnos;
    }

    /**
     * Define pontuação de bombeiros baseados em alguns critérios:
     *  - Se é Querubim = 1000000 pontos
     *  - Se a cidade de origem é a mesma do quartel = 1000
     *  - Se tem carteira = 100 pontos
     *  - Grau de formação
     * 
     * @param bool $zerarPontuacao Precisamos resetar a pontuação pois esta função é chamada várias vezes
     */
    public function computarPontuacaoBombeiros(bool $zerarPontuacao = false) {

        foreach ($this->bombeiros as &$bombeiro) {

            if ($zerarPontuacao) {
                $bombeiro->setPontuacao(0);

                // TODO: Melhorar essa tratativa
                if ( $bombeiro->getNome() == 'BC CHEROBIN ' || intval($bombeiro->getCpf()) === CbmscConstants::CPF_DO_QUERUBIN ) {
                    $bombeiro->setPontuacao(CbmscConstants::PONTUACAO_QUERUBIN);
                }

                switch($bombeiro->getCidadeOrigem()) {
                    case CbmscConstants::CIDADE_VIDEIRA:
                        $bombeiro->setPontuacao($bombeiro->getPontuacao() + CbmscConstants::PONTUACAO_VIDEIRA);
                        break;
                    default:
                        $bombeiro->setPontuacao($bombeiro->getPontuacao() + CbmscConstants::PONTUACAO_OUTRAS_CIDADES);
                        break;
                }
    
                if ($bombeiro->getCarteiraAmbulancia()) {
                    $bombeiro->setPontuacao($bombeiro->getPontuacao() + CbmscConstants::PONTUACAO_CARTEIRA_AMBULANCIA);
                }

                $bombeiro->setPontuacao(
                    $bombeiro->getPontuacao() + $this->calculadorDeAntiguidade->getAntiguidade($bombeiro)
                );
            }


            // Cada dia que o bombeiro ganha joga ele 1000 pontos para trás
            if (count($bombeiro->getTurnosAdquiridos()) !== 0) {
                $reducao = count($bombeiro->getTurnosAdquiridos()) * 1000;
                $bombeiro->setPontuacao($bombeiro->getPontuacao() - $reducao);
            }
        }
    }

    private function getHorasPorTurno(string $turno) {
        return match ($turno) {
            CbmscConstants::TURNO_DIURNO => 12,
            CbmscConstants::TURNO_NOTURNO => 12,
            CbmscConstants::TURNO_INTEGRAL => 24,
        };
    }

    /**
     * Distribui turnos para um dia específico até atingir a quantidade de horas desejada
     * 
     * @param array $bombeirosDisponiveisParaDia Array de bombeiros disponíveis para o dia
     * @param int $dia Dia do mês
     * @param string $turno Tipo de turno (integral, diurno, noturno)
     * @param int $horasPorDia Quantidade total de horas a distribuir por dia
     * @param int $horasDoDiaDistribuidas Referência à variável que armazena horas já distribuídas (será modificada)
     * @param array $todosOsTurnos Referência ao array que armazena todos os turnos (será modificado)
     */
    private function distribuirTurnoParaDia(array $bombeirosDisponiveisParaDia, int $dia, string $turno, int $horasPorDia, int &$horasDoDiaDistribuidas, array &$todosOsTurnos, ?int $limiteCotasDistribuidas = null): void {
        $bombeirosDisponiveisParaTurno = array_values(array_filter($bombeirosDisponiveisParaDia, function(Bombeiro $bombeiro) use ($dia, $turno): bool {
            return $bombeiro->temDisponibilidade($dia, $turno);
        }));
        $bombeirosPorPercentual = $this->ordenaBombeirosPorPercentualDeServicosAceitos($bombeirosDisponiveisParaTurno);
        $bombeirosOrdenados = $this->ordenaBombeirosPorPontuacao($bombeirosPorPercentual, $dia);
        $cotasDistribuidas = 0;

        /**
         * @var Bombeiro $bombeiro
         */
        foreach ($bombeirosOrdenados as $bombeiro) {
            if ($horasDoDiaDistribuidas >= $horasPorDia) {
                break;
            }

            if ($limiteCotasDistribuidas !== null && $cotasDistribuidas >= $limiteCotasDistribuidas) {
                break;
            }

            // Verifica quantos turnos integrais o bombeiro já adquiriu
            $turnosIntegraisAdquiridos = count(array_filter($bombeiro->getTurnosAdquiridos(), function(Turno $turno) {
                return $turno->getTurno() == CbmscConstants::TURNO_INTEGRAL;
            }));

            // Se o bombeiro já atingiu o limite de turnos integrais, pula para o próximo bombeiro
            if ($turnosIntegraisAdquiridos >= CbmscConstants::COTAS_INTEGRAIS_POR_MES) {
                continue;
            }

            $horasDoDiaDistribuidas += $this->getHorasPorTurno($turno);
            $todosOsTurnos[$dia][$turno][] = $bombeiro;
            $bombeiro->adicionaTurnoAdquirido(new Turno($dia, $turno));
            $cotasDistribuidas++;
        }
    }

    private function obtemHorasDistribuidas(array $turnosDoDia) {
        $horas = 0;

        foreach ($turnosDoDia as $keyTurno => $bombeiros) {
            $horas += $this->getHorasPorTurno($keyTurno) * count($bombeiros);
        }

        return $horas;
    }

    /**
     * Decompõe cotas integrais em turnos parciais (diurno ou noturno) quando o dia não foi preenchido com todas as horas
     * 
     * @param array $bombeirosDisponiveisParaDia Array de bombeiros disponíveis para o dia
     * @param int $dia Dia do mês
     * @param int $horasPorDia Quantidade total de horas a distribuir por dia
     * @param array $todosOsTurnos Referência ao array que armazena todos os turnos (será modificado)
     */
    private function decomporCotasIntegraisEmTurnoParcial(array $bombeirosDisponiveisParaDia, int $dia, int $horasPorDia, array &$todosOsTurnos): void {
        if (!isset($todosOsTurnos[$dia])) {
            return;
        }

        $horasDistribuidasParaDia = $this->obtemHorasDistribuidas($todosOsTurnos[$dia]);

        while ($horasDistribuidasParaDia < $horasPorDia) {
            $cotasDiurnas = isset($todosOsTurnos[$dia][CbmscConstants::TURNO_DIURNO]) ? count($todosOsTurnos[$dia][CbmscConstants::TURNO_DIURNO]) : 0;
            $cotasNoturnas = isset($todosOsTurnos[$dia][CbmscConstants::TURNO_NOTURNO]) ? count($todosOsTurnos[$dia][CbmscConstants::TURNO_NOTURNO]) : 0;

            $bombeirosTurnoIntegralDiponiveis = array_values(array_filter($bombeirosDisponiveisParaDia, function(Bombeiro $bombeiro) use ($dia): bool {
                return 
                    $bombeiro->temDisponibilidade($dia, CbmscConstants::TURNO_INTEGRAL) &&
                    ! in_array($dia, array_map(function(Turno $turno) {
                        return $turno->getDia();
                    }, $bombeiro->getTurnosAdquiridos()));
            }));

            if (empty($bombeirosTurnoIntegralDiponiveis)) {
                break;
            }

            $bombeirosPorPercentual = $this->ordenaBombeirosPorPercentualDeServicosAceitos($bombeirosTurnoIntegralDiponiveis);
            $bombeirosOrdenados = $this->ordenaBombeirosPorPontuacao($bombeirosPorPercentual, $dia);

            if ($cotasDiurnas <= $cotasNoturnas) {
                $todosOsTurnos[$dia][CbmscConstants::TURNO_DIURNO][] = $bombeirosOrdenados[0];
                $bombeirosOrdenados[0]->adicionaTurnoAdquirido(new Turno($dia, CbmscConstants::TURNO_DIURNO, true));
            } else {
                $todosOsTurnos[$dia][CbmscConstants::TURNO_NOTURNO][] = $bombeirosOrdenados[0];
                $bombeirosOrdenados[0]->adicionaTurnoAdquirido(new Turno($dia, CbmscConstants::TURNO_NOTURNO, true));
            }

            // Atualiza as horas distribuídas após adicionar o bombeiro
            $horasDistribuidasParaDia = $this->obtemHorasDistribuidas($todosOsTurnos[$dia]);
        }
    }

    /**
     * Distribui todos os turnos para cada dia do mês baseado nas regras de prioridade
     * 
     * @param int|float $horasPorDia Quantidade de horas por dia que desejamos distribuir
     * @param array|null $diasSelecionados Array de dias do mês (1-31) para processar, ou null para processar todos os dias
     */
    public function distribuirTurnosParaMes(int $horasPorDia = 60, ?array $diasSelecionados = null){
        $todosOsTurnos = [];

        // Store selected days for motorista adicional verification
        $this->diasQuePrecisaMotoristaAdicional = $diasSelecionados;

        // Primeiro processa turnos dos dias que precisam de motorista adicional
        if ($this->diasQuePrecisaMotoristaAdicional !== null && count($this->diasQuePrecisaMotoristaAdicional) > 0) {
            foreach ($this->diasQuePrecisaMotoristaAdicional as $dia) {
                $this->distribuirTurnosParaDia($dia, $horasPorDia, $todosOsTurnos);
            }
        }
        
        // Depois processa turnos dos dias que não precisam de motorista adicional
        for ($dia = 1; $dia <= 31; $dia++) {
            if (!in_array($dia, $this->diasQuePrecisaMotoristaAdicional ?? [])) {
                $this->distribuirTurnosParaDia($dia, $horasPorDia, $todosOsTurnos);
            }
        }

        $this->garantirTurnoParaBombeirosSemTurno($todosOsTurnos, $horasPorDia);

        /**
         * Revisa cada dia para ter certeza de que a distribuição ficou justa.
         * Idealmente desejamos que cada bombeiro tenha uma distribuição equivalente de horários,
         * ou seja, o mesmo % de horários solicitados x distribuidos
         * 
         * Isso precisar ser feito depois da distribuição de dias, pois só aqui sabemos
         * o % de destribuição para cada bombeiro.
         */
        // foreach ($todosOsTurnos as $dia => $turnos) {
        //     $turnos_do_dia = $this->computarTurnosDoDia($dia);

        //     if ($dia == 22){
        //         $a = 1;
        //     }
        //     foreach ($turnos as $turnoKey => $turno) {
        //         $todosOsTurnos[$dia][$turnoKey] = $this->getBombeirosPorPrioridade($turnos_do_dia[$turnoKey], $cotas);
        //     }
        // }

        return $todosOsTurnos;
    }

    public function distribuirTurnosParaDia(int $dia, int $horasPorDia, array &$todosOsTurnos) {
        $bombeirosDisponiveisParaDia = $this->obtemBombeirosDisponiveisParaDia($dia);
        $horasDoDiaDistribuidas = 0;
        $this->computarPontuacaoBombeiros(true);

        /**
         * Limita a distribuição de cotas integrais, pois se tivermos 2.5 cotas por dia, não podemos ter mais de 2 cotas integrais.
         */
        $limiteCotasIntegral = floor($horasPorDia / (2 * CbmscConstants::MEIA_COTA_EM_HORAS));
        $this->distribuirTurnoParaDia($bombeirosDisponiveisParaDia, $dia, CbmscConstants::TURNO_INTEGRAL, $horasPorDia, $horasDoDiaDistribuidas, $todosOsTurnos, $limiteCotasIntegral);

        $horasRestantes = $horasPorDia - $horasDoDiaDistribuidas;
        $meiasCotasRestantes = $horasRestantes / CbmscConstants::MEIA_COTA_EM_HORAS;

        $meiasCotasNecessariasParaTurnoNoturno = $this->getMeiasCotasNecessariasParaUmDiaETurno($bombeirosDisponiveisParaDia, $dia, CbmscConstants::TURNO_NOTURNO);
        
        // Calcula quantas meias cotas queremos distribuir para o turno diurno e noturno
        $meiasCotasDiurno = ceil($meiasCotasRestantes / 2);
        $meiasCotasNoturno = floor($meiasCotasRestantes / 2);

        // Se noturno pode consumir seu % de cotas, diurno será limitado ao seu % também.
        if ( $meiasCotasNecessariasParaTurnoNoturno >= $meiasCotasNoturno) {
            $limiteCotasDistribuidasParaTurnoDiurno = $meiasCotasDiurno;
        } else {
            // Senão, diurno consome o restante das cotas que o noturno não pode consumir
            $limiteCotasDistribuidasParaTurnoDiurno = $meiasCotasRestantes - $meiasCotasNecessariasParaTurnoNoturno;
        }


        // Seta o consumo de meias cotas para o turno diurno baseado na oferta de trabalho de ambos os turnos DIURNO e NOTURNO
        $this->distribuirTurnoParaDia($bombeirosDisponiveisParaDia, $dia, CbmscConstants::TURNO_DIURNO, $horasPorDia, $horasDoDiaDistribuidas, $todosOsTurnos, $limiteCotasDistribuidasParaTurnoDiurno);


        // Noturno usa as cotas restantes que o diurno deixou para ele
        $this->distribuirTurnoParaDia($bombeirosDisponiveisParaDia, $dia, CbmscConstants::TURNO_NOTURNO, $horasPorDia, $horasDoDiaDistribuidas, $todosOsTurnos);

        // Se ainda não preencheu o dia com todas as horas, tenta decompor cotas integrais restantes em Diurno ou Noturno
        $this->decomporCotasIntegraisEmTurnoParcial($bombeirosDisponiveisParaDia, $dia, $horasPorDia, $todosOsTurnos);

        // // Após fazer a distribuição do dia, verifica se precisávamos de motorista adicional e valida se atingimos o objtivo.
        // if ($this->verificarSePrecisaMotoristaAdicional($dia)) {
        //     // Filtra bombeiros que possuem o turno escolhido para o dia
        //     $bomberiosSelcionadosParaDiaComCarteira = array_values(array_filter($bombeirosDisponiveisParaDia, function(Bombeiro $bombeiro) use ($dia): bool {
        //         // verifica no getTurnosAdquiridos do bombeiro se ele foi alocado para o dia
        //         return in_array($dia, array_map(function(Turno $turno) {
        //             return $turno->getDia();
        //         }, $bombeiro->getTurnosAdquiridos())) && $bombeiro->getCarteiraAmbulancia();
        //     }));
            
        //     // Se nenhum bombeiro escolhido possui carteira, troca algum deles por um que possui e atende o dia e turno escolhido
        //     if (empty($bomberiosSelcionadosParaDiaComCarteira)) {
        //         // Filtra bombeiros que possuem carteira de ambulância
        //         $bomberiosSelcionadosParaDiaComCarteira = array_values(array_filter($bombeirosDisponiveisParaDia, function(Bombeiro $bombeiro) use ($dia): bool {
        //             return $bombeiro->getCarteiraAmbulancia() && $bombeiro->getDisponibilidade($dia);
        //         }));

        //         // Algum deles possui período integral?
        //         $bombeiroComPeriodoIntegral = array_values(array_filter($bomberiosSelcionadosParaDiaComCarteira, function(Bombeiro $bombeiro) use ($dia): bool {
        //             return $bombeiro->getDisponibilidade($dia)->getTurno() == CbmscConstants::TURNO_INTEGRAL;
        //         }));

        //         if (empty($bombeiroComPeriodoIntegral)) {
        //             // Nenhum deles possui período integral, troca algum deles por um que possui e atende o dia e turno escolhido
        //             $bombeiroComPeriodoIntegral = $bomberiosSelcionadosParaDiaComCarteira[0];
        //         }
        //         // Está muito específico isso, precisa ficar mais genérico.
        //     }
        // }

        // Precisamos recomputar a pontuação pois cada vez que um bombeiro é selecionado volta para o "fim da fila"
        $this->computarPontuacaoBombeiros(true);

        return $todosOsTurnos;
    }

    /**
     * Garante que todos os bombeiros tenham pelo menos um turno
     * 
     * @param array $todosOsTurnos Referência ao array que armazena todos os turnos (será modificado)
     * @param int $horasPorDia Quantidade de horas por dia que desejamos distribuir
     */
    private function garantirTurnoParaBombeirosSemTurno(array &$todosOsTurnos, int $horasPorDia): void {
        // Encontra todos os bombeiros que não receberam nenhum turno
        $bombeirosSemTurno = array_filter($this->bombeiros, function(Bombeiro $bombeiro): bool {
            return count($bombeiro->getTurnosAdquiridos()) === 0;
        });

        if (empty($bombeirosSemTurno)) {
            return; // Todos os bombeiros já têm pelo menos um turno
        }

        // Para cada bombeiro sem turno, tenta encontrar um dia e turno disponível
        foreach ($bombeirosSemTurno as $bombeiro) {
            $turnoAtribuido = false;

            // Tenta encontrar um dia onde o bombeiro tem disponibilidade
            for ($dia = 1; $dia <= 31 && !$turnoAtribuido; $dia++) {
                if (!$bombeiro->temDisponibilidadeParaDia($dia)) {
                    continue; // Bombeiro não tem disponibilidade neste dia
                }

                // Tenta atribuir um turno, priorizando turnos menores primeiro para minimizar o impacto
                // e preferindo dias onde ainda há espaço disponível
                $turnosParaTentar = [
                    CbmscConstants::TURNO_DIURNO,    // 12h
                    CbmscConstants::TURNO_NOTURNO,   // 12h
                    CbmscConstants::TURNO_INTEGRAL    // 24h
                ];

                // Primeiro, tenta encontrar um turno que caiba perfeitamente no limite de horas
                foreach ($turnosParaTentar as $turno) {
                    if ($bombeiro->temDisponibilidade($dia, $turno)) {                        
                        // Verifica se alguém desse turno já teve mais que 1 turno no mês e troca essa pessoa pelo bombeiro
                        // Obtém todos os bombeiros daquele dia 

                        // Extrais todos os bombeiros do dia a partir dos array de turnos
                        $bombeirosDoDia = [];
                        foreach ($todosOsTurnos[$dia] as $turno => $bombeiros) {
                            foreach ($bombeiros as $b) {
                                $bombeirosDoDia[] = $b;
                            }
                        }

                        // Verifica se alguém desse turno já teve mais que 1 turno no mês e troca essa pessoa pelo bombeiro
                        $bombeirosComMaisDeUmTurno = array_filter($bombeirosDoDia, function($bombeiro) {
                            return count($bombeiro->getTurnosAdquiridos()) > 1;
                        });
                        
                        if (count($bombeirosComMaisDeUmTurno) > 0) {
                            // Verifica se algum deles tem o mesmo turno que o bombeiro sem turno e troca essa pessoa pelo bombeiro
                            $bombeirosComMismoTurno = array_values(array_filter($bombeirosComMaisDeUmTurno, function($bombeiro) use ($dia, $turno) {
                                return $bombeiro->getDisponibilidade($dia)->getTurno() == $turno;
                            }));
                            
                            /**
                             * Aplicar isso globalmente, pois aqui temos uma cópia de dados do array de turnos.
                             * Idealmente deveríamos apenas trabalhar com o objeto original de bombeiros e a partir dele converter para o array de turnos.
                             */
                            if (count($bombeirosComMismoTurno) > 0) {
                                // TODO: Por hora estamos aplicando tudo ao bombeiro e ao objeto de todosOsTurnos. Temos que trabalhar com o array de objetos de bombeiros para evitar cópias de dados e ter uma única source of truth.
                                $bombeirosComMismoTurno[0]->removerTurnoAdquirido(new Turno($dia, $turno));
                                // Remove o bombeiro do array de turnos
                                $todosOsTurnos[$dia][$turno] = array_values(array_filter($todosOsTurnos[$dia][$turno], function($b) use ($bombeirosComMismoTurno) {
                                    return $b->getNome() != $bombeirosComMismoTurno[0]->getNome();
                                }));

                                $bombeiro->adicionaTurnoAdquirido(new Turno($dia, $turno));
                                $todosOsTurnos[$dia][$turno][] = $bombeiro;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        // Recomputa a pontuação após atribuir turnos adicionais
        $this->computarPontuacaoBombeiros(true);
    }

    /**
     * Obtém o número de cotas somadas para um array de bombeiros
     * Só funciona para turno DIURNO e NOTURNO
     */
    private function getMeiasCotasNecessariasParaUmDiaETurno(array $bombeiros, int $dia, string $turno) {
        $bombeirosDisponiveisParaTurno = array_values(array_filter($bombeiros, function(Bombeiro $bombeiro) use ($dia, $turno): bool {
            return $bombeiro->temDisponibilidade($dia, $turno);
        }));

        return count($bombeirosDisponiveisParaTurno);
    }

    /**
     * Aplica bubble sort para deixar bombeiros com a maior pontuação primeiro
     */
    private function ordenaBombeirosPorPontuacao(array $bombeiros, $dia = null) {
        $nowData = null;

        // $this->computarPontuacaoBombeiros(true);

        for ($i = 0; $i < count($bombeiros); $i++) {
            
            // TODO: Implementar a lógica de pontuação para motorista fora daqui
            if ($bombeiros[$i]->getCarteiraAmbulancia() && $this->verificarSePrecisaMotoristaAdicional($dia)) {
                // $bombeiros[$i]->setPontuacao($bombeiros[$i]->getPontuacao() + CbmscConstants::PONTUACAO_CARTEIRA_AMBULANCIA);
            }
            
            // TODO: Refatorar esse código.
            for ($j = 0; $j < count($bombeiros); $j++) {
                $pontuacaoTemporariaDiaI = $bombeiros[$i]->getCarteiraAmbulancia() && $this->verificarSePrecisaMotoristaAdicional($dia) ? 100000 : 0;
                $pontuacaoTemporariaDiaJ = $bombeiros[$j]->getCarteiraAmbulancia() && $this->verificarSePrecisaMotoristaAdicional($dia) ? 100000 : 0;

                // if ($pontuacaoTemporariaDia > 0) {
                //     // echo "nome: " . $bombeiros[$i]->getNome() . " - pontuacao: " . ($bombeiros[$i]->getPontuacao() + $pontuacaoTemporariaDia) . " - bombeiros[$j]->getPontuacao(): " . $bombeiros[$j]->getPontuacao() . "<br>";
                // }

                // echo "nome: " . $bombeiros[$i]->getNome() . " - pontuacao: " . ($bombeiros[$i]->getPontuacao() + $pontuacaoTemporariaDia) . " - bombeiros[$j]->getPontuacao(): " . $bombeiros[$j]->getPontuacao() . "<br>";
                if ($bombeiros[$i]->getPontuacao() + $pontuacaoTemporariaDiaI > $bombeiros[$j]->getPontuacao() + $pontuacaoTemporariaDiaJ) {
                    $nowData = $bombeiros[$i];
                    $bombeiros[$i] = $bombeiros[$j];
                    $bombeiros[$j] = $nowData;
                }
            }
        }

        return $bombeiros;
    }

    private function verificarSePrecisaMotoristaAdicional(int $dia) {
        // Use the selected days from distribuirTurnosParaMes, or return false if not set
        if ($this->diasQuePrecisaMotoristaAdicional === null) {
            return false;
        }

        return in_array($dia, $this->diasQuePrecisaMotoristaAdicional);
    }

    /**
     * Aplica bubble sort para deixar bombeiros com menor percentual de serviços primeiro
     */
    public function ordenaBombeirosPorPercentualDeServicosAceitos(array $bombeiros) {
        $nowData = null;

        for ($i = 0; $i < count($bombeiros); $i++) {
            for ($j = 0; $j < count($bombeiros); $j++) {
                if ($bombeiros[$i]->getPercentualDeServicosAceitos() > $bombeiros[$j]->getPercentualDeServicosAceitos()) {
                    $nowData = $bombeiros[$i];
                    $bombeiros[$i] = $bombeiros[$j];
                    $bombeiros[$j] = $nowData;
                }
            }
        }

        return $bombeiros;
    }

    /**
     * Computa os turnos para um dia específico
     * 
     * @param int $dia
     */
    public function computarTurnosDoDia(int $dia) {
        $turnos_do_dia = [
            CbmscConstants::TURNO_DIURNO => [],
            CbmscConstants::TURNO_NOTURNO => [],
            CbmscConstants::TURNO_INTEGRAL => []
        ];

        // Para cada bombeiro, obtem o turno do dia atual
        foreach ($this->bombeiros as $bombeiro) {
            if ($bombeiro->temDisponibilidade($dia, CbmscConstants::TURNO_DIURNO)) {
                $turnos_do_dia[CbmscConstants::TURNO_DIURNO][] = $bombeiro;
            } else if ($bombeiro->temDisponibilidade($dia, CbmscConstants::TURNO_NOTURNO)) {
                $turnos_do_dia[CbmscConstants::TURNO_NOTURNO][] = $bombeiro;
            } else if ($bombeiro->temDisponibilidade($dia, CbmscConstants::TURNO_INTEGRAL)) {
                $turnos_do_dia[CbmscConstants::TURNO_INTEGRAL][] = $bombeiro;
            }
        }

        return $turnos_do_dia;
    }

    public function obtemBombeirosDisponiveisParaDia(int $dia) {
        return array_values(array_filter($this->bombeiros, function(Bombeiro $bombeiro) use ($dia): bool {
            return $bombeiro->temDisponibilidadeParaDia($dia);
        }));
    }

    // TODO: Remover este método daqui, afinal ele é usado apenas para debugging e não pertence ao cálculo de turnos.
    public function print_turnos_do_mes(int $dia) {
        $todosOsTurnos = $this->computarTodosOsTurnos();
        
        if (!isset($todosOsTurnos[$dia])) {
            echo "<p style='color: red; font-weight: bold;'>❌ Dia {$dia} não encontrado!</p>";
            return;
        }
        
        echo "<div style='width: 45%; float: left; border: 1px solid #ddd; margin: 10px 20px 0 0; padding: 15px;'>";
        echo "<h3 style='color: #333; margin-top: 0;'>📅 DIA {$dia} - ESCALAÇÃO DE TURNOS</h3>";
        
        // Contar total de bombeiros
        $totalBombeiros = 0;
        foreach ($todosOsTurnos[$dia]['turnos'] as $bombeiros) {
            $totalBombeiros += count($bombeiros);
        }
        
        echo "<p><strong>Total de bombeiros disponíveis:</strong> {$totalBombeiros}</p>";
        
        // Mostrar cada turno e seus bombeiros
        foreach ($todosOsTurnos[$dia]['turnos'] as $turno => $bombeiros) {
            $icon = $this->getTurnoIcon($turno);
            $count = count($bombeiros);
            
            echo "<div style='margin: 10px 0; padding: 10px; border-left: 4px solid " . $this->getTurnoColor($turno) . ";'>";
            echo "<h4 style='margin: 0 0 8px 0; color: " . $this->getTurnoColor($turno) . ";'>";
            echo "{$icon} {$turno} ({$count} bombeiro" . ($count != 1 ? 's' : '') . ")";
            echo "</h4>";
            
            if (empty($bombeiros)) {
                echo "<p style='color: #888; font-style: italic; margin: 0;'>⚠️ Nenhum bombeiro disponível</p>";
            } else {
                echo "<ul style='margin: 5px 0; padding-left: 20px;'>";
                foreach ($bombeiros as $bombeiro) {
                    $badges = [];
                    if ($bombeiro->getCarteiraAmbulancia()) {
                        $badges[] = "🚑";
                    }

                    $badges[] = $bombeiro->getPontuacao() . " pts";
                    
                    echo "<li style='margin: 3px 0;'>";
                    echo "<strong>{$bombeiro->getNome()}</strong>";
                    echo " <span style='color: #666; font-size: 12px;'>(" . implode(', ', $badges) . ")</span>";
                    echo "</li>";
                }
                echo "</ul>";
            }
            echo "</div>";
        }

        echo "</div>";
    }

    /**
     * Retorna o ícone para cada tipo de turno
     */
    private function getTurnoIcon(string $turno) {
        switch ($turno) {
            case CbmscConstants::TURNO_DIURNO:
                return "☀️";
            case CbmscConstants::TURNO_NOTURNO:
                return "🌙";
            case CbmscConstants::TURNO_INTEGRAL:
                return "⏰";
            default:
                return "❓";
        }
    }

    /**
     * Retorna a cor para cada tipo de turno
     */
    private function getTurnoColor($turno) {
        switch ($turno) {
            case CbmscConstants::TURNO_DIURNO:
                return "#f39c12"; // Laranja
            case CbmscConstants::TURNO_NOTURNO:
                return "#34495e"; // Azul escuro
            case CbmscConstants::TURNO_INTEGRAL:
                return "#9b59b6"; // Roxo
            default:
                return "#95a5a6"; // Cinza
        }
    }
    
    /**
     * Adiciona um bombeiro ao array de bombeiros
     */
    public function adicionarBombeiro(Bombeiro $bombeiro) {
        $this->bombeiros[] = $bombeiro;
    }
}

// print "O serviço foi marcado com sucesso para o dia {$dia} do mes {$this->getMes()} com os bombeiros: {$bombeiro1->getNome()}, {$bombeiro2->getNome()} e {$bombeiro3->geNome()}.";