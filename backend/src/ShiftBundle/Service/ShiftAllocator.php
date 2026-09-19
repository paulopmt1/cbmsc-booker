<?php

namespace App\ShiftBundle\Service;

use App\Constants\CbmscConstants;
use App\FiremanBundle\Entity\FiremanEntity;
use App\FiremanBundle\Service\SeniorityCalculator;
use App\ShiftBundle\Entity\ShiftEntity;

class ShiftAllocator {

    
    public function __construct(
        private readonly SeniorityCalculator $seniorityCalculator,
        private readonly int $priorityFirefighterCpf
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
     * @var $firefighters array<FiremanEntity>
     */
    private $firefighters = [];

    /**
     * Array de dias que precisam de motorista adicional
     * @var array|null
     */
    private ?array $daysRequiringAdditionalDriver = null;

    /**
     * Usada para debugging mostrando quais são todos os bombeiros disponíveis para cada dia do mês.
     */
    public function computarTodosOsTurnos() {
        $allShifts = [];

        // Para cada dia do mês, computamos os turnos dos bombeiros
        for ($day = 1; $day <= 31; $day++) {
            $dailyShifts = $this->computarTurnosDoDia($day);

            // Adiciona o dia e os turnos ao array de turnos do mês
            $allShifts[$day] = [
                'dia' => $day,
                'turnos' => $dailyShifts
            ];
        }

        return $allShifts;
    }

    /**
     * Define pontuação de bombeiros baseados em alguns critérios:
     *  - Se é Querubim = 1000000 pontos
     *  - Se a cidade de origem é a mesma do quartel = 1000
     *  - Se tem carteira = 100 pontos
     *  - Grau de formação
     * 
     * @param bool $resetScore Precisamos resetar a pontuação pois esta função é chamada várias vezes
     */
    public function computarPontuacaoBombeiros(bool $resetScore = false) {

        foreach ($this->firefighters as &$firefighter) {

            if ($resetScore) {
                $firefighter->setPontuacao(0);


                if (intval($firefighter->getCpf()) === $this->priorityFirefighterCpf) {
                    $firefighter->setPontuacao(CbmscConstants::PONTUACAO_QUERUBIN);
                }

                switch($firefighter->getCidadeOrigem()) {
                    case CbmscConstants::CIDADE_VIDEIRA:
                        $firefighter->setPontuacao($firefighter->getPontuacao() + CbmscConstants::PONTUACAO_VIDEIRA);
                        break;
                    default:
                        $firefighter->setPontuacao($firefighter->getPontuacao() + CbmscConstants::PONTUACAO_OUTRAS_CIDADES);
                        break;
                }
    
                if ($firefighter->getCarteiraAmbulancia()) {
                    $firefighter->setPontuacao($firefighter->getPontuacao() + CbmscConstants::PONTUACAO_CARTEIRA_AMBULANCIA);
                }

                $firefighter->setPontuacao(
                    $firefighter->getPontuacao() + $this->seniorityCalculator->getAntiguidade($firefighter)
                );
            }


            // Cada dia que o bombeiro ganha joga ele 1000 pontos para trás
            if (count($firefighter->getTurnosAdquiridos()) !== 0) {
                $scoreReduction = count($firefighter->getTurnosAdquiridos()) * 1000;
                $firefighter->setPontuacao($firefighter->getPontuacao() - $scoreReduction);
            }
        }
    }

    private function getHorasPorTurno(string $shift) {
        return match ($shift) {
            CbmscConstants::TURNO_DIURNO => 12,
            CbmscConstants::TURNO_NOTURNO => 12,
            CbmscConstants::TURNO_INTEGRAL => 24,
        };
    }

    /**
     * Distribui turnos para um dia específico até atingir a quantidade de horas desejada
     * 
     * @param array $firefightersAvailableForDay Array de bombeiros disponíveis para o dia
     * @param int $day Dia do mês
     * @param string $shift Tipo de turno (integral, diurno, noturno)
     * @param int $dailyHours Quantidade total de horas a distribuir por dia
     * @param int $allocatedDailyHours Referência à variável que armazena horas já distribuídas (será modificada)
     * @param array $allShifts Referência ao array que armazena todos os turnos (será modificado)
     */
    private function distribuirTurnoParaDia(array $firefightersAvailableForDay, int $day, string $shift, int $dailyHours, int &$allocatedDailyHours, array &$allShifts, ?int $distributedQuotaLimit = null): void {
        $firefightersAvailableForShift = array_values(array_filter($firefightersAvailableForDay, function(FiremanEntity $firefighter) use ($day, $shift): bool {
            return $firefighter->temDisponibilidade($day, $shift);
        }));
        $firefightersByPercentage = $this->ordenaBombeirosPorPercentualDeServicosAceitos($firefightersAvailableForShift);
        $sortedFirefighters = $this->ordenaBombeirosPorPontuacao($firefightersByPercentage, $day);
        $distributedQuotas = 0;

        /**
         * @var FiremanEntity $firefighter
         */
        foreach ($sortedFirefighters as $firefighter) {
            if ($allocatedDailyHours >= $dailyHours) {
                break;
            }

            if ($distributedQuotaLimit !== null && $distributedQuotas >= $distributedQuotaLimit) {
                break;
            }

            // Verifica quantos turnos integrais o bombeiro já adquiriu
            $acquiredFullShifts = count(array_filter($firefighter->getTurnosAdquiridos(), function(ShiftEntity $shift) {
                return $shift->getTurno() == CbmscConstants::TURNO_INTEGRAL;
            }));

            // Se o bombeiro já atingiu o limite de turnos integrais, pula para o próximo bombeiro
            if ($acquiredFullShifts >= CbmscConstants::COTAS_INTEGRAIS_POR_MES) {
                continue;
            }

            $allocatedDailyHours += $this->getHorasPorTurno($shift);
            $allShifts[$day][$shift][] = $firefighter;
            $firefighter->adicionaTurnoAdquirido(new ShiftEntity($day, $shift));
            $distributedQuotas++;
        }
    }

    private function obtemHorasDistribuidas(array $dailyShifts) {
        $hours = 0;

        foreach ($dailyShifts as $shiftKey => $firefighters) {
            $hours += $this->getHorasPorTurno($shiftKey) * count($firefighters);
        }

        return $hours;
    }

    /**
     * Decompõe cotas integrais em turnos parciais (diurno ou noturno) quando o dia não foi preenchido com todas as horas
     * 
     * @param array $firefightersAvailableForDay Array de bombeiros disponíveis para o dia
     * @param int $day Dia do mês
     * @param int $dailyHours Quantidade total de horas a distribuir por dia
     * @param array $allShifts Referência ao array que armazena todos os turnos (será modificado)
     */
    private function decomporCotasIntegraisEmTurnoParcial(array $firefightersAvailableForDay, int $day, int $dailyHours, array &$allShifts): void {
        if (!isset($allShifts[$day])) {
            return;
        }

        $hoursAllocatedForDay = $this->obtemHorasDistribuidas($allShifts[$day]);

        while ($hoursAllocatedForDay < $dailyHours) {
            $dayQuotas = isset($allShifts[$day][CbmscConstants::TURNO_DIURNO]) ? count($allShifts[$day][CbmscConstants::TURNO_DIURNO]) : 0;
            $nightQuotas = isset($allShifts[$day][CbmscConstants::TURNO_NOTURNO]) ? count($allShifts[$day][CbmscConstants::TURNO_NOTURNO]) : 0;

            $firefightersAvailableForFullShift = array_values(array_filter($firefightersAvailableForDay, function(FiremanEntity $firefighter) use ($day): bool {
                return 
                    $firefighter->temDisponibilidade($day, CbmscConstants::TURNO_INTEGRAL) &&
                    ! in_array($day, array_map(function(ShiftEntity $shift) {
                        return $shift->getDia();
                    }, $firefighter->getTurnosAdquiridos()));
            }));

            if (empty($firefightersAvailableForFullShift)) {
                break;
            }

            $firefightersByPercentage = $this->ordenaBombeirosPorPercentualDeServicosAceitos($firefightersAvailableForFullShift);
            $sortedFirefighters = $this->ordenaBombeirosPorPontuacao($firefightersByPercentage, $day);

            if ($dayQuotas <= $nightQuotas) {
                $allShifts[$day][CbmscConstants::TURNO_DIURNO][] = $sortedFirefighters[0];
                $sortedFirefighters[0]->adicionaTurnoAdquirido(new ShiftEntity($day, CbmscConstants::TURNO_DIURNO, true));
            } else {
                $allShifts[$day][CbmscConstants::TURNO_NOTURNO][] = $sortedFirefighters[0];
                $sortedFirefighters[0]->adicionaTurnoAdquirido(new ShiftEntity($day, CbmscConstants::TURNO_NOTURNO, true));
            }

            // Atualiza as horas distribuídas após adicionar o bombeiro
            $hoursAllocatedForDay = $this->obtemHorasDistribuidas($allShifts[$day]);
        }
    }

    /**
     * Distribui todos os turnos para cada dia do mês baseado nas regras de prioridade
     * 
     * @param int|float $dailyHours Quantidade de horas por dia que desejamos distribuir
     * @param array|null $selectedDays Array de dias do mês (1-31) para processar, ou null para processar todos os dias
     */
    public function distribuirTurnosParaMes(int $dailyHours = 60, ?array $selectedDays = null){
        $allShifts = [];

        // Store selected days for motorista adicional verification
        $this->daysRequiringAdditionalDriver = $selectedDays;

        // Primeiro processa turnos dos dias que precisam de motorista adicional
        if ($this->daysRequiringAdditionalDriver !== null && count($this->daysRequiringAdditionalDriver) > 0) {
            foreach ($this->daysRequiringAdditionalDriver as $day) {
                $this->distribuirTurnosParaDia($day, $dailyHours, $allShifts);
            }
        }
        
        // Depois processa turnos dos dias que não precisam de motorista adicional
        for ($day = 1; $day <= 31; $day++) {
            if (!in_array($day, $this->daysRequiringAdditionalDriver ?? [])) {
                $this->distribuirTurnosParaDia($day, $dailyHours, $allShifts);
            }
        }

        $this->garantirTurnoParaBombeirosSemTurno($allShifts, $dailyHours);

        /**
         * Revisa cada dia para ter certeza de que a distribuição ficou justa.
         * Idealmente desejamos que cada bombeiro tenha uma distribuição equivalente de horários,
         * ou seja, o mesmo % de horários solicitados x distribuidos
         * 
         * Isso precisar ser feito depois da distribuição de dias, pois só aqui sabemos
         * o % de destribuição para cada bombeiro.
         */
        // foreach ($allShifts as $day => $shifts) {
        //     $dailyShifts = $this->computarTurnosDoDia($day);

        //     if ($day == 22){
        //         $a = 1;
        //     }
        //     foreach ($shifts as $shiftKey => $shift) {
        //         $allShifts[$day][$shiftKey] = $this->getBombeirosPorPrioridade($dailyShifts[$shiftKey], $quotas);
        //     }
        // }

        return $allShifts;
    }

    public function distribuirTurnosParaDia(int $day, int $dailyHours, array &$allShifts) {
        $firefightersAvailableForDay = $this->obtemBombeirosDisponiveisParaDia($day);
        $allocatedDailyHours = 0;
        $this->computarPontuacaoBombeiros(true);

        /**
         * Limita a distribuição de cotas integrais, pois se tivermos 2.5 cotas por dia, não podemos ter mais de 2 cotas integrais.
         */
        $fullShiftQuotaLimit = floor($dailyHours / (2 * CbmscConstants::MEIA_COTA_EM_HORAS));
        $this->distribuirTurnoParaDia($firefightersAvailableForDay, $day, CbmscConstants::TURNO_INTEGRAL, $dailyHours, $allocatedDailyHours, $allShifts, $fullShiftQuotaLimit);

        $remainingHours = $dailyHours - $allocatedDailyHours;
        $remainingHalfQuotas = $remainingHours / CbmscConstants::MEIA_COTA_EM_HORAS;

        $halfQuotasRequiredForNightShift = $this->getMeiasCotasNecessariasParaUmDiaETurno($firefightersAvailableForDay, $day, CbmscConstants::TURNO_NOTURNO);
        
        // Calcula quantas meias cotas queremos distribuir para o turno diurno e noturno
        $dayHalfQuotas = ceil($remainingHalfQuotas / 2);
        $nightHalfQuotas = floor($remainingHalfQuotas / 2);

        // Se noturno pode consumir seu % de cotas, diurno será limitado ao seu % também.
        if ( $halfQuotasRequiredForNightShift >= $nightHalfQuotas) {
            $dayShiftQuotaLimit = $dayHalfQuotas;
        } else {
            // Senão, diurno consome o restante das cotas que o noturno não pode consumir
            $dayShiftQuotaLimit = $remainingHalfQuotas - $halfQuotasRequiredForNightShift;
        }


        // Seta o consumo de meias cotas para o turno diurno baseado na oferta de trabalho de ambos os turnos DIURNO e NOTURNO
        $this->distribuirTurnoParaDia($firefightersAvailableForDay, $day, CbmscConstants::TURNO_DIURNO, $dailyHours, $allocatedDailyHours, $allShifts, $dayShiftQuotaLimit);


        // Noturno usa as cotas restantes que o diurno deixou para ele
        $this->distribuirTurnoParaDia($firefightersAvailableForDay, $day, CbmscConstants::TURNO_NOTURNO, $dailyHours, $allocatedDailyHours, $allShifts);

        // Se ainda não preencheu o dia com todas as horas, tenta decompor cotas integrais restantes em Diurno ou Noturno
        $this->decomporCotasIntegraisEmTurnoParcial($firefightersAvailableForDay, $day, $dailyHours, $allShifts);

        // // Após fazer a distribuição do dia, verifica se precisávamos de motorista adicional e valida se atingimos o objtivo.
        // if ($this->verificarSePrecisaMotoristaAdicional($day)) {
        //     // Filtra bombeiros que possuem o turno escolhido para o dia
        //     $licensedFirefightersSelectedForDay = array_values(array_filter($firefightersAvailableForDay, function(FiremanEntity $firefighter) use ($day): bool {
        //         // verifica no getTurnosAdquiridos do bombeiro se ele foi alocado para o dia
        //         return in_array($day, array_map(function(ShiftEntity $shift) {
        //             return $shift->getDia();
        //         }, $firefighter->getTurnosAdquiridos())) && $firefighter->getCarteiraAmbulancia();
        //     }));
            
        //     // Se nenhum bombeiro escolhido possui carteira, troca algum deles por um que possui e atende o dia e turno escolhido
        //     if (empty($licensedFirefightersSelectedForDay)) {
        //         // Filtra bombeiros que possuem carteira de ambulância
        //         $licensedFirefightersSelectedForDay = array_values(array_filter($firefightersAvailableForDay, function(FiremanEntity $firefighter) use ($day): bool {
        //             return $firefighter->getCarteiraAmbulancia() && $firefighter->getDisponibilidade($day);
        //         }));

        //         // Algum deles possui período integral?
        //         $fullShiftFirefighter = array_values(array_filter($licensedFirefightersSelectedForDay, function(FiremanEntity $firefighter) use ($day): bool {
        //             return $firefighter->getDisponibilidade($day)->getTurno() == CbmscConstants::TURNO_INTEGRAL;
        //         }));

        //         if (empty($fullShiftFirefighter)) {
        //             // Nenhum deles possui período integral, troca algum deles por um que possui e atende o dia e turno escolhido
        //             $fullShiftFirefighter = $licensedFirefightersSelectedForDay[0];
        //         }
        //         // Está muito específico isso, precisa ficar mais genérico.
        //     }
        // }

        // Precisamos recomputar a pontuação pois cada vez que um bombeiro é selecionado volta para o "fim da fila"
        $this->computarPontuacaoBombeiros(true);

        return $allShifts;
    }

    /**
     * Garante que todos os bombeiros tenham pelo menos um turno
     * 
     * @param array $allShifts Referência ao array que armazena todos os turnos (será modificado)
     * @param int $dailyHours Quantidade de horas por dia que desejamos distribuir
     */
    private function garantirTurnoParaBombeirosSemTurno(array &$allShifts, int $dailyHours): void {
        // Encontra todos os bombeiros que não receberam nenhum turno
        $firefightersWithoutShift = array_filter($this->firefighters, function(FiremanEntity $firefighter): bool {
            return count($firefighter->getTurnosAdquiridos()) === 0;
        });

        if (empty($firefightersWithoutShift)) {
            return; // Todos os bombeiros já têm pelo menos um turno
        }

        // Para cada bombeiro sem turno, tenta encontrar um dia e turno disponível
        foreach ($firefightersWithoutShift as $firefighter) {
            $shiftAssigned = false;

            // Tenta encontrar um dia onde o bombeiro tem disponibilidade
            for ($day = 1; $day <= 31 && !$shiftAssigned; $day++) {
                if (!$firefighter->temDisponibilidadeParaDia($day)) {
                    continue; // Bombeiro não tem disponibilidade neste dia
                }

                // Tenta atribuir um turno, priorizando turnos menores primeiro para minimizar o impacto
                // e preferindo dias onde ainda há espaço disponível
                $candidateShifts = [
                    CbmscConstants::TURNO_DIURNO,    // 12h
                    CbmscConstants::TURNO_NOTURNO,   // 12h
                    CbmscConstants::TURNO_INTEGRAL    // 24h
                ];

                // Primeiro, tenta encontrar um turno que caiba perfeitamente no limite de horas
                foreach ($candidateShifts as $shift) {
                    if ($firefighter->temDisponibilidade($day, $shift)) {                        
                        // Verifica se alguém desse turno já teve mais que 1 turno no mês e troca essa pessoa pelo bombeiro
                        // Obtém todos os bombeiros daquele dia 

                        // Extrais todos os bombeiros do dia a partir dos array de turnos
                        $firefightersForDay = [];
                        foreach ($allShifts[$day] as $shift => $firefighters) {
                            foreach ($firefighters as $b) {
                                $firefightersForDay[] = $b;
                            }
                        }

                        // Verifica se alguém desse turno já teve mais que 1 turno no mês e troca essa pessoa pelo bombeiro
                        $firefightersWithMultipleShifts = array_filter($firefightersForDay, function($firefighter) {
                            return count($firefighter->getTurnosAdquiridos()) > 1;
                        });
                        
                        if (count($firefightersWithMultipleShifts) > 0) {
                            // Verifica se algum deles tem o mesmo turno que o bombeiro sem turno e troca essa pessoa pelo bombeiro
                            $firefightersWithSameShift = array_values(array_filter($firefightersWithMultipleShifts, function($firefighter) use ($day, $shift) {
                                return $firefighter->getDisponibilidade($day)->getTurno() == $shift;
                            }));
                            
                            /**
                             * Aplicar isso globalmente, pois aqui temos uma cópia de dados do array de turnos.
                             * Idealmente deveríamos apenas trabalhar com o objeto original de bombeiros e a partir dele converter para o array de turnos.
                             */
                            if (count($firefightersWithSameShift) > 0) {
                                // TODO: Por hora estamos aplicando tudo ao bombeiro e ao objeto de todosOsTurnos. Temos que trabalhar com o array de objetos de bombeiros para evitar cópias de dados e ter uma única source of truth.
                                $firefightersWithSameShift[0]->removerTurnoAdquirido(new ShiftEntity($day, $shift));
                                // Remove o bombeiro do array de turnos
                                $allShifts[$day][$shift] = array_values(array_filter($allShifts[$day][$shift], function($b) use ($firefightersWithSameShift) {
                                    return $b->getNome() != $firefightersWithSameShift[0]->getNome();
                                }));

                                $firefighter->adicionaTurnoAdquirido(new ShiftEntity($day, $shift));
                                $allShifts[$day][$shift][] = $firefighter;
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
    private function getMeiasCotasNecessariasParaUmDiaETurno(array $firefighters, int $day, string $shift) {
        $firefightersAvailableForShift = array_values(array_filter($firefighters, function(FiremanEntity $firefighter) use ($day, $shift): bool {
            return $firefighter->temDisponibilidade($day, $shift);
        }));

        return count($firefightersAvailableForShift);
    }

    /**
     * Aplica bubble sort para deixar bombeiros com a maior pontuação primeiro
     */
    private function ordenaBombeirosPorPontuacao(array $firefighters, $day = null) {
        $nowData = null;

        // $this->computarPontuacaoBombeiros(true);

        for ($i = 0; $i < count($firefighters); $i++) {
            
            // TODO: Implementar a lógica de pontuação para motorista fora daqui
            if ($firefighters[$i]->getCarteiraAmbulancia() && $this->verificarSePrecisaMotoristaAdicional($day)) {
                // $firefighters[$i]->setPontuacao($firefighters[$i]->getPontuacao() + CbmscConstants::PONTUACAO_CARTEIRA_AMBULANCIA);
            }
            
            // TODO: Refatorar esse código.
            for ($j = 0; $j < count($firefighters); $j++) {
                $temporaryDailyScoreI = $firefighters[$i]->getCarteiraAmbulancia() && $this->verificarSePrecisaMotoristaAdicional($day) ? 100000 : 0;
                $temporaryDailyScoreJ = $firefighters[$j]->getCarteiraAmbulancia() && $this->verificarSePrecisaMotoristaAdicional($day) ? 100000 : 0;

                // if ($temporaryDailyScore > 0) {
                //     // echo "nome: " . $firefighters[$i]->getNome() . " - pontuacao: " . ($firefighters[$i]->getPontuacao() + $temporaryDailyScore) . " - bombeiros[$j]->getPontuacao(): " . $firefighters[$j]->getPontuacao() . "<br>";
                // }

                // echo "nome: " . $firefighters[$i]->getNome() . " - pontuacao: " . ($firefighters[$i]->getPontuacao() + $temporaryDailyScore) . " - bombeiros[$j]->getPontuacao(): " . $firefighters[$j]->getPontuacao() . "<br>";
                if ($firefighters[$i]->getPontuacao() + $temporaryDailyScoreI > $firefighters[$j]->getPontuacao() + $temporaryDailyScoreJ) {
                    $nowData = $firefighters[$i];
                    $firefighters[$i] = $firefighters[$j];
                    $firefighters[$j] = $nowData;
                }
            }
        }

        return $firefighters;
    }

    private function verificarSePrecisaMotoristaAdicional(int $day) {
        // Use the selected days from distribuirTurnosParaMes, or return false if not set
        if ($this->daysRequiringAdditionalDriver === null) {
            return false;
        }

        return in_array($day, $this->daysRequiringAdditionalDriver);
    }

    /**
     * Aplica bubble sort para deixar bombeiros com menor percentual de serviços primeiro
     */
    public function ordenaBombeirosPorPercentualDeServicosAceitos(array $firefighters) {
        $nowData = null;

        for ($i = 0; $i < count($firefighters); $i++) {
            for ($j = 0; $j < count($firefighters); $j++) {
                if ($firefighters[$i]->getPercentualDeServicosAceitos() > $firefighters[$j]->getPercentualDeServicosAceitos()) {
                    $nowData = $firefighters[$i];
                    $firefighters[$i] = $firefighters[$j];
                    $firefighters[$j] = $nowData;
                }
            }
        }

        return $firefighters;
    }

    /**
     * Computa os turnos para um dia específico
     * 
     * @param int $day
     */
    public function computarTurnosDoDia(int $day) {
        $dailyShifts = [
            CbmscConstants::TURNO_DIURNO => [],
            CbmscConstants::TURNO_NOTURNO => [],
            CbmscConstants::TURNO_INTEGRAL => []
        ];

        // Para cada bombeiro, obtem o turno do dia atual
        foreach ($this->firefighters as $firefighter) {
            if ($firefighter->temDisponibilidade($day, CbmscConstants::TURNO_DIURNO)) {
                $dailyShifts[CbmscConstants::TURNO_DIURNO][] = $firefighter;
            } else if ($firefighter->temDisponibilidade($day, CbmscConstants::TURNO_NOTURNO)) {
                $dailyShifts[CbmscConstants::TURNO_NOTURNO][] = $firefighter;
            } else if ($firefighter->temDisponibilidade($day, CbmscConstants::TURNO_INTEGRAL)) {
                $dailyShifts[CbmscConstants::TURNO_INTEGRAL][] = $firefighter;
            }
        }

        return $dailyShifts;
    }

    public function obtemBombeirosDisponiveisParaDia(int $day) {
        return array_values(array_filter($this->firefighters, function(FiremanEntity $firefighter) use ($day): bool {
            return $firefighter->temDisponibilidadeParaDia($day);
        }));
    }

    // TODO: Remover este método daqui, afinal ele é usado apenas para debugging e não pertence ao cálculo de turnos.
    public function print_turnos_do_mes(int $day) {
        $allShifts = $this->computarTodosOsTurnos();
        
        if (!isset($allShifts[$day])) {
            echo "<p style='color: red; font-weight: bold;'>❌ Dia {$day} não encontrado!</p>";
            return;
        }
        
        echo "<div style='width: 45%; float: left; border: 1px solid #ddd; margin: 10px 20px 0 0; padding: 15px;'>";
        echo "<h3 style='color: #333; margin-top: 0;'>📅 DIA {$day} - ESCALAÇÃO DE TURNOS</h3>";
        
        // Contar total de bombeiros
        $totalFirefighters = 0;
        foreach ($allShifts[$day]['turnos'] as $firefighters) {
            $totalFirefighters += count($firefighters);
        }
        
        echo "<p><strong>Total de bombeiros disponíveis:</strong> {$totalFirefighters}</p>";
        
        // Mostrar cada turno e seus bombeiros
        foreach ($allShifts[$day]['turnos'] as $shift => $firefighters) {
            $icon = $this->getTurnoIcon($shift);
            $count = count($firefighters);
            
            echo "<div style='margin: 10px 0; padding: 10px; border-left: 4px solid " . $this->getTurnoColor($shift) . ";'>";
            echo "<h4 style='margin: 0 0 8px 0; color: " . $this->getTurnoColor($shift) . ";'>";
            echo "{$icon} {$shift} ({$count} bombeiro" . ($count != 1 ? 's' : '') . ")";
            echo "</h4>";
            
            if (empty($firefighters)) {
                echo "<p style='color: #888; font-style: italic; margin: 0;'>⚠️ Nenhum bombeiro disponível</p>";
            } else {
                echo "<ul style='margin: 5px 0; padding-left: 20px;'>";
                foreach ($firefighters as $firefighter) {
                    $badges = [];
                    if ($firefighter->getCarteiraAmbulancia()) {
                        $badges[] = "🚑";
                    }

                    $badges[] = $firefighter->getPontuacao() . " pts";
                    
                    echo "<li style='margin: 3px 0;'>";
                    echo "<strong>{$firefighter->getNome()}</strong>";
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
    private function getTurnoIcon(string $shift) {
        switch ($shift) {
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
    private function getTurnoColor($shift) {
        switch ($shift) {
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
    public function adicionarBombeiro(FiremanEntity $firefighter) {
        $this->firefighters[] = $firefighter;
    }
}

// print "O serviço foi marcado com sucesso para o dia {$day} do mes {$this->getMes()} com os bombeiros: {$firefighter1->getNome()}, {$firefighter2->getNome()} e {$firefighter3->geNome()}.";
