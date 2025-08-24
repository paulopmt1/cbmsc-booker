<?php

namespace App\Entity;

class Servico {
    // Atributos
    private $dia = [];
    private $mes;
    private $nomeDoBombeiro;

    private $turno; // manhã, tarde, noite, integral(24 horas?)

    private $bombeiros = [];

    // Métodos

    /* public function __construct($bombeiro1, $bombeiro2, $bombeiro3, $mes) {
        $this->bombeiro1 = $bombeiro1;
        $this->bombeiro2 = $bombeiro2;
        $this->bombeiro3 = $bombeiro3;

        $this->bombeiros[] = $bombeiro1;
        $this->bombeiros[] = $bombeiro2;
        $this->bombeiros[] = $bombeiro3;
        $this->setMes($mes);
    } */

    public function resolverConflitos($turno, $dias, $data) {
        // Turnos

        $manha = [];
        $tarde = [];
        $noite = [];
        $integral = [];
        $bombeiros = [];
        $contagemTurnos = [ "manhã" => 0, "tarde" => 0, "noite" => 0, "integral" => 0 ];

        // Adiciona um valor para cada vez que o turno for escolhido
        foreach ($contagemTurnos as $nomeTurno => $valor) {
            if ($turno == $nomeTurno) {
                $contagemTurnos[$nomeTurno]++;
            }
        }
        
        // Adiciona os bombeiros com maior pontuação no array de cada turno

        while (count($manha) > 3) {
            for ($i = 0; $i < count($bombeiros) -1; $i++) {
                if ($bombeiros[$i]->getPontuacao() < $bombeiros[$i + 1]->getPontuacao()) {
                    $manha[] = $bombeiros[$i + 1];
                } else {
                    $manha[] = $bombeiros[$i];
                }
                if (count($manha) == 3) {
                    break;
                }
            }
        }

        while (count($tarde) > 3) {
            for ($i = 0; $i < count($bombeiros) -1; $i++) {
                if ($bombeiros[$i]->getPontuacao() < $bombeiros[$i + 1]->getPontuacao()) {
                    $tarde[] = $bombeiros[$i + 1];
                } else {
                    $tarde[] = $bombeiros[$i];
                }
                if (count($tarde) == 3) {
                    break;
                }
            }
        }

        while (count($noite) > 3) {
            for ($i = 0; $i < count($bombeiros) -1; $i++) {
                if ($bombeiros[$i]->getPontuacao() < $bombeiros[$i + 1]->getPontuacao()) {
                    $noite[] = $bombeiros[$i + 1];
                } else {
                    $noite[] = $bombeiros[$i];
                }
                if (count($noite) == 3) {
                    break;
                }
            }
        }

        while (count($integral) > 1) {
            for ($i = 0; $i < count($bombeiros) -1; $i++) {
                if ($bombeiros[$i]->getPontuacao() < $bombeiros[$i + 1]->getPontuacao()) {
                    $integral[] = $bombeiros[$i + 1];
                } else {
                    $integral[] = $bombeiros[$i];
                }
                if (count($integral) == 3) {
                    break;
                }
            }
        }
    
        $dias[$data] = [
            'turno' => 'I' || 'D' || 'N',
        ];
    }

    // Getters e Setters

    public function getDia() {
        return $this->dia;
    }

    public function setDia($dia) {
        $this->dia = $dia;
    }

    public function getMes() {
        return $this->mes;
    }

    public function setMes($mes) {
        $this->mes = $mes;
    }

    public function getTurno() {
        return $this->turno;
    }

    public function setTurno($turno) {
        $this->turno = $turno;
    }

    public function getNomeDoBombeiro() {
        return $this->nomeDoBombeiro;
    }

    public function setNomeDoBombeiro($nomeDoBombeiro) {
        $this->nomeDoBombeiro = $nomeDoBombeiro;
    }

}

// print "O serviço foi marcado com sucesso para o dia {$dia} do mes {$this->getMes()} com os bombeiros: {$bombeiro1->getNome()}, {$bombeiro2->getNome()} e {$bombeiro3->geNome()}.";