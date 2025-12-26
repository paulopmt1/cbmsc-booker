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
    private $diasAdquiridos = 0;
    private $pontuacao = 0;

    // Construtor
    public function __construct($nome, $cpf, $carteiraAmbulancia){
        $this->setNome($nome);
        $this->setCpf($cpf);
        $this->setCarteiraAmbulancia($carteiraAmbulancia);
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

    public function setAntiguidade(int $antiguidade) {
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

    /**
     * Obtem a disponibilidade para um dia específico
     * @param int $dia
     * @return App\Entity\Disponibilidade|null
     */
    public function getDisponibilidade(int $dia): ?Disponibilidade {
        foreach ($this->disponibilidades as $disponibilidade) {
            if ($disponibilidade->getDia() == $dia) {
                return $disponibilidade;
            }
        }
        return null;
    }

    public function getDiasAdquiridos() {
        return $this->diasAdquiridos;
    }

    public function increaseDiasAdquiridos() {
        $this->diasAdquiridos++;
    }

    public function decreaseDiasAdquiridos() {
        $this->diasAdquiridos--;
    }

    public function getDiasSolicitados() {
        // Isso garante que mesmo na varredura de % ele sempre seja o primeiro
        if ($this->nome == 'BC CHEROBIN ') {
            return 100000000000;
        }

        return count($this->disponibilidades);
    }

    public function setPontuacao(int $pontos) {
        $this->pontuacao = $pontos;
    }

    public function getPontuacao() {
        return $this->pontuacao;
    }

    public function getPercentualDeServicosAceitos() {
        return round($this->getDiasAdquiridos() * 100 / $this->getDiasSolicitados(), 2);
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

    /**
     * Verifica se o bombeiro tem disponibilidade para um dia e turno específico
     */
    public function temDisponibilidade($dia, $turno) {
        foreach ($this->disponibilidades as $disponibilidade) {
            if ($disponibilidade->getDia() == $dia && $disponibilidade->getTurno() == $turno) {
                return true;
            }
        }
        return false;
    }

    public function temDisponibilidadeParaDia(int $dia) {
        foreach ($this->disponibilidades as $disponibilidade) {
            if ($disponibilidade->getDia() == $dia) {
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