<?php

require_once 'skeleton/src/Entity/classBombeiro.php';

class Servico {
    // Atributos
    private $dia = [];
    private $mes;
    private $nomeDoBombeiro;

    private $turno; // manhã, tarde, noite, integral(24 horas?)

    private $bombeiro1;
    private $bombeiro2;
    private $bombeiro3;

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

    public function resolverConflitos() {
        // Turnos
        $manha = [];
        $tarde = [];
        $noite = [];
        $integral = [];

        $bombeiros = [];

        
        while (count($manha) > 3) {
            for ($i = 0; $i < count($bombeiros); $i++) {
                if ($bombeiros[$i]->getPontuacao() < $bombeiros[$i + 1]->getPontuacao()) {
                    $manha[] = $bombeiros[$i];
                }
            }
        }
    
    
    
        while (count($tarde) > 3) {
            for ($i = 0; $i < count($bombeiros); $i++) {
                if ($bombeiros[$i]->getPontuacao() < $bombeiros[$i +1]->getPontuacao()) {
                    $tarde[] = $bombeiros[$i];
                }
            }
        }
    

    
        while (count($noite) > 3) {
            for ($i = 0; $i < count($bombeiros); $i++) {
                if ($bombeiros[$i]->getPontuacao() < $bombeiros[$i + 1]->getPontuacao()) {
                    $noite[] = $bombeiros[$i];
                }
            }
        }
    

    
        while (count($integral) > 3) {
            for ($i = 0; $i < count($bombeiros); $i++) {
                if ($bombeiros[$i]->getPontuacao() < $bombeiros[$i +1]->getPontuacao()) {
                    $integral[] = $bombeiros[$i];
                }
            }
        }
        

        $dia = [$manha, $tarde, $noite, $integral];
        return $dia;

        
    }

    public function escolherDiaServico($nome, $dia, $mes, $turno) {
        $this->setNomeDoBombeiro($nome);
        $this->setDia($dia);
        $this->setMes($mes);
        $this->setTurno($turno);

        $escolha = [];
        $escolha = [$nome, $dia, $mes, $turno];

        return $escolha;
        
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