<?php

namespace App\Entity;

use App\Entity\Disponibilidade;



class Bombeiro {
    // Atributos
    private $nome;
    private $cpf;
    private $antiguidade = 0;
    private $carteiraAmbulancia = false;
    private $cidadeOrigem;
    private $disponibilidades = []; // Array de objetos Disponibilidade

    // Construtor
    public function __construct($nome, $cpf, $antiguidade, $carteiraAmbulancia, $cidadeOrigem){
        $this->setNome($nome);
        $this->setCpf($cpf);
        $this->setAntiguidade($antiguidade);
        $this->setCarteiraAmbulancia($carteiraAmbulancia);
        $this->setCidadeOrigem($cidadeOrigem);
    }

    public function adicionarDisponibilidadeServico($mes, $dias, $turno) {
        // Cria uma nova instância de Disponibilidade e adiciona ao array
        $disponibilidade = new Disponibilidade($dias, $mes, $turno);
        $this->adicionarDisponibilidade($disponibilidade);
    }

    public function exibirDados() {
        return "Nome: {$this->getNome()}, CPF: {$this->getCpf()}, Antiguidade: {$this->getAntiguidade()}, Carteira Ambulância: {$this->getCarteiraAmbulancia()}";
    }

    // Getters and Setters
    public function getNome() {
        return $this->nome;
    }

    public function setNome($nome) {
        $this->nome = $nome;

    }

    public function getCpf() {
        return $this->cpf;
    }

    public function setCpf($cpf) {
        $this->cpf = $cpf;
    }

    public function getAntiguidade() {
        return $this->antiguidade;
    }

    public function setAntiguidade($antiguidade) {
        $this->antiguidade = $antiguidade;
    }

    public function getCarteiraAmbulancia() {
        return $this->carteiraAmbulancia;
    }

    public function setCarteiraAmbulancia($carteiraAmbulancia) {
        $this->carteiraAmbulancia = $carteiraAmbulancia;
    }

    public function getCidadeOrigem() {
        return $this->cidadeOrigem;
    }

    public function setCidadeOrigem($cidadeOrigem) {
        $this->cidadeOrigem = $cidadeOrigem;
    }

    public function getDisponibilidade() {
        return $this->disponibilidades;
    }

    public function setDisponibilidade(array $disponibilidade) {
        $this->disponibilidades = $disponibilidade;
    }

    /**
     * Adiciona um objeto Disponibilidade ao array
     */
    public function adicionarDisponibilidade(Disponibilidade $disponibilidade): void {
        $this->disponibilidades[] = $disponibilidade;
    }

    /**
     * Remove uma disponibilidade específica
     */
    public function removerDisponibilidade(Disponibilidade $disponibilidade): bool {
        foreach ($this->disponibilidades as $key => $disp) {
            if ($disp->equals($disponibilidade)) {
                unset($this->disponibilidades[$key]);
                $this->disponibilidades = array_values($this->disponibilidades); // Re-index array
                return true;
            }
        }
        return false;
    }

    // Método para imprimir a disponibilidade do bombeiro sem Xdebug
    public function print_disponibilidade() {
        foreach ($this->disponibilidades as $disponibilidade) {
            echo $disponibilidade . "<br>";
        }
    }

}