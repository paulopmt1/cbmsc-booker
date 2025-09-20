<?php

namespace App\Service;

use App\Constants\CbmscConstants;
use App\Entity\Bombeiro;
use App\Service\CalculadorDeAntiguidade;

class CalculadorDePontos {

    
    public function __construct(
        private readonly CalculadorDeAntiguidade $calculadorDeAntiguidade
    ) {
    }

    /**
     * Aqui definimos quantos BCs por turno podemos ter
     * Esse termo é chamado de cotas, hoje suportamos 6 cotas de 12h ou seja 3 de 24h,
     * ou seja, 3 BCs durante o dia e 3 durante a noite simultaneamente
     */
    private $quotasDe12hPorTurno = [
        CbmscConstants::TURNO_DIURNO => 2,
        CbmscConstants::TURNO_NOTURNO => 2,
        CbmscConstants::TURNO_INTEGRAL => 1,
    ];

    /**
     * Array de bombeiros que serão utilizados para o serviço do mês
     * @var $bombeiros App\Entity\Bombeiro
     */
    private $bombeiros = [];

    /**
     * Computa os turnos dos bombeiros o mês inteiro e adiciona ao array de turnos
     * assim sabemos quantos bombeiros temos para cada turno
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
                if ( $bombeiro->getNome() == 'BC CHEROBIN ' || $bombeiro->getCpf() === CbmscConstants::CPF_DO_QUERUBIN ) {
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
            if ($bombeiro->getDiasAdquiridos() !== 0) {
                $reducao = $bombeiro->getDiasAdquiridos() * 1000;
                $bombeiro->setPontuacao($bombeiro->getPontuacao() - $reducao);
            }
        }
    }

    /**
     * Distribui todos os turnos para cada dia do mês baseado nas regras de prioridade
     */
    public function distribuirTurnosParaMes(){
        $todosOsTurnos = [];

        // Para cada dia do mês, distribui serviços
        for ($dia = 1; $dia <= 31; $dia++) {
            $turnos_do_dia = $this->computarTurnosDoDia($dia);
            $turnos = [];
            
            foreach ($this->quotasDe12hPorTurno as $turno => $cotas) {
                $turnos[$turno] = $this->getBombeirosPorPrioridade($turnos_do_dia[$turno], $cotas);
            }
            
            $todosOsTurnos[$dia] = $turnos;

            // Precisamos recomputar a pontuação pois cada vez que um bombeiro é selecionado volta para o "fim da fila"
            $this->computarPontuacaoBombeiros(true);
        }

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

    private function getBombeirosPorPrioridade(array $bombeiros, int $numberoBombeiros) {
        $bombeirosPorPercentual = $this->ordenaBombeirosPorPercentualDeServicosAceitos($bombeiros);

        $bombeirosOrdenados = $this->ordenaBombeirosPorPontuacao($bombeirosPorPercentual);
        $bombeirosSelecionados = array_splice($bombeirosOrdenados, 0, $numberoBombeiros);

        foreach($bombeirosSelecionados as &$bombeiro) {
            $bombeiro->increaseDiasAdquiridos();
        }

        return $bombeirosSelecionados;
    }

    /**
     * Aplica bubble sort para deixar bombeiros com a maior pontuação primeiro
     */
    private function ordenaBombeirosPorPontuacao(&$bombeiros) {
        $nowData = null;

        for ($i = 0; $i < count($bombeiros); $i++) {
            for ($j = 0; $j < count($bombeiros); $j++) {
                if ($bombeiros[$i]->getPontuacao() > $bombeiros[$j]->getPontuacao()) {
                    $nowData = $bombeiros[$i];
                    $bombeiros[$i] = $bombeiros[$j];
                    $bombeiros[$j] = $nowData;
                }
            }
        }

        return $bombeiros;
    }

    /**
     * Aplica bubble sort para deixar bombeiros com menor percentual de serviços primeiro
     */
    public function ordenaBombeirosPorPercentualDeServicosAceitos(&$bombeiros) {
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

    public function print_turnos_do_mes(int $dia) {
        $todosOsTurnos = $this->computarTodosOsTurnos();
        
        if (!isset($todosOsTurnos[$dia])) {
            echo "<p style='color: red; font-weight: bold;'>❌ Dia {$dia} não encontrado!</p>";
            return;
        }
        
        $dadosDia = $todosOsTurnos[$dia];
        
        echo "<div style='width: 45%; float: left; border: 1px solid #ddd; margin: 10px 20px 0 0; padding: 15px;'>";
        echo "<h3 style='color: #333; margin-top: 0;'>📅 DIA {$dia} - ESCALAÇÃO DE TURNOS</h3>";
        
        // Contar total de bombeiros
        $totalBombeiros = 0;
        foreach ($dadosDia['turnos'] as $bombeiros) {
            $totalBombeiros += count($bombeiros);
        }
        
        echo "<p><strong>Total de bombeiros disponíveis:</strong> {$totalBombeiros}</p>";
        
        // Mostrar cada turno e seus bombeiros
        foreach ($dadosDia['turnos'] as $turno => $bombeiros) {
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