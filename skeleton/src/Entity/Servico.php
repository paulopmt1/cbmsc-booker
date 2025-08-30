<?php

namespace App\Entity;

use App\Constants\CbmscConstants;

class Servico {
    // Atributos
    private $contagemTurnos = [ 
        CbmscConstants::TURNO_DIURNO => 0, 
        CbmscConstants::TURNO_NOTURNO => 0, 
        CbmscConstants::TURNO_INTEGRAL => 0 
    ];

    /**
     * 1 cota = 12h (DIURNO) + 12h (NOTURNO) ou 24h (INTEGRAL) de trabalho
     * Hoje, suportamos 3 cotas por dia
     * 1 cota pode conter 1 período INTEGRAL ou 2 meio períodos (NOTURNO + DIURNO)
     * Aqui temos as constantes que armazenam as horas de cada período
     */
    private $maximoDeCotas = 3;

    /**
     * Array de bombeiros que serão utilizados para o serviço do mês
     */
    private $bombeiros = [];

    /**
     * Array de turnos do mês
     */
    private $turnos_do_mes = [];

    /**
     * Array de conflitos
     */
    private $conflitos = [];

    /**
     * Computa os turnos dos bombeiros o mês inteiro e adiciona ao array de turnos
     * assim sabemos quantos bombeiros temos para cada turno
     */
    public function computarTurnos() {
        $turnos_do_dia = [
            CbmscConstants::TURNO_DIURNO => [],
            CbmscConstants::TURNO_NOTURNO => [],
            CbmscConstants::TURNO_INTEGRAL => []
        ];

        // Para cada dia do mês, computamos os turnos dos bombeiros
        for ($dia = 1; $dia <= 31; $dia++) {
            // Para cada bombeiro, obtem o turno do dia atual
            foreach ($this->bombeiros as $bombeiro) {
                // Verifica se o bombeiro tem disponibilidade para o dia atual
                if ($bombeiro->temDisponibilidade($dia, CbmscConstants::TURNO_DIURNO)) {
                    $turnos_do_dia[CbmscConstants::TURNO_DIURNO][] = $bombeiro;
                } else if ($bombeiro->temDisponibilidade($dia, CbmscConstants::TURNO_NOTURNO)) {
                    $turnos_do_dia[CbmscConstants::TURNO_NOTURNO][] = $bombeiro;
                } else if ($bombeiro->temDisponibilidade($dia, CbmscConstants::TURNO_INTEGRAL)) {
                    $turnos_do_dia[CbmscConstants::TURNO_INTEGRAL][] = $bombeiro;
                }
            }

            // Adiciona o dia e os turnos ao array de turnos do mês
            $this->turnos_do_mes[$dia] = [
                'dia' => $dia,
                'turnos' => $turnos_do_dia,
                'conflitos' => []
            ];
        }
    }

    public function resolverConflitos() {
    }


    public function print_turnos_do_mes($dia) {
        if (!isset($this->turnos_do_mes[$dia])) {
            echo "<p style='color: red; font-weight: bold;'>❌ Dia {$dia} não encontrado!</p>";
            return;
        }

        $dadosDia = $this->turnos_do_mes[$dia];
        
        echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px; background-color: #f9f9f9;'>";
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
            
            echo "<div style='margin: 10px 0; padding: 10px; background-color: white; border-left: 4px solid " . $this->getTurnoColor($turno) . ";'>";
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
                    $badges[] = $bombeiro->getCidadeOrigem();
                    $badges[] = $bombeiro->getAntiguidade() . "a";
                    
                    echo "<li style='margin: 3px 0;'>";
                    echo "<strong>{$bombeiro->getNome()}</strong>";
                    echo " <span style='color: #666; font-size: 12px;'>(" . implode(', ', $badges) . ")</span>";
                    echo "</li>";
                }
                echo "</ul>";
            }
            echo "</div>";
        }
        
        // Mostrar conflitos se existirem
        if (!empty($dadosDia['conflitos'])) {
            echo "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
            echo "<h4 style='color: #856404; margin: 0 0 5px 0;'>⚠️ CONFLITOS</h4>";
            foreach ($dadosDia['conflitos'] as $conflito) {
                echo "<p style='margin: 2px 0; color: #856404;'>• {$conflito}</p>";
            }
            echo "</div>";
        }

        echo "</div>";
    }

    /**
     * Retorna o ícone para cada tipo de turno
     */
    private function getTurnoIcon($turno) {
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

    public function getConflitos() {
        return $this->conflitos;
    }
    
    /**
     * Adiciona um bombeiro ao array de bombeiros
     */
    public function adicionarBombeiro(Bombeiro $bombeiro) {
        $this->bombeiros[] = $bombeiro;
    }
}

// print "O serviço foi marcado com sucesso para o dia {$dia} do mes {$this->getMes()} com os bombeiros: {$bombeiro1->getNome()}, {$bombeiro2->getNome()} e {$bombeiro3->geNome()}.";