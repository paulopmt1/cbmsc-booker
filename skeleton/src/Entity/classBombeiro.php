<?php

require_once 'skeleton/src/Entity/classServico.php';

class Bombeiro {
    // Atributos
    private $nome;
    private $cpf;
    private $antiguidade = 0;
    private $carteiraAmbulancia = false;
    private $pontuacao = 0;
    private $cidadeOrigem;
    private $dia = [];
    private $mes;
    private $turno; // manhã, tarde, noite, integral(24 horas?)

    // Construtor
    public function __construct($nome, $cpf, $antiguidade, $carteiraAmbulancia, $cidadeOrigem){
        $this->setNome($nome);
        $this->setCpf($cpf);
        $this->setAntiguidade($antiguidade);
        $this->setCarteiraAmbulancia($carteiraAmbulancia);
        $this->setCidadeOrigem($cidadeOrigem);

        //

        if ($nome == "Querubin") {
            $this->setPontuacao(1000); // Exemplo de pontuação especial
        }

        switch ($cidadeOrigem) {
            case "Videira":
                $this->setPontuacao($this->getPontuacao() + 20);
                break;
            case "Fraiburgo":
                $this->setPontuacao($this->getPontuacao() + 15);
                break;
            case "Caçador":
                $this->setPontuacao($this->getPontuacao() + 10);
                break;
            default:
                $this->setPontuacao($this->getPontuacao() + 5);
                break;
        }

        if ($carteiraAmbulancia == true) {
            $this->setPontuacao($this->getPontuacao() + 10);
        }

    }

    public function calcularAntiguidade($antiguidade) {
        switch ($antiguidade) {
            case $antiguidade < 2:
                $this->setPontuacao($this->getPontuacao() + 5);
                break;
            case $antiguidade > 2 && $antiguidade < 5:
                $this->setPontuacao($this->getPontuacao() + 10);
                break;
            case $antiguidade >= 5:
                $this->setPontuacao($this->getPontuacao() + 15);
                break;
            case $antiguidade < 10:
                $this->setPontuacao($this->getPontuacao() + 20);
                break;
        }
    }

    // Métodos

    public function escolherDiaServico($nome, $dia, $mes, $turno) {

        $this->setNome($nome);
        $this->setDia($dia);
        $this->setMes($mes);
        $this->setTurno($turno);

        $escolha = [];
        $escolha = [$nome, $dia, $mes, $turno];

        return $escolha;
        
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

    public function getPontuacao() {
        return $this->pontuacao;
    }

    public function setPontuacao($pontuacao) {
        $this->pontuacao = $pontuacao;
    }

    public function getCidadeOrigem() {
        return $this->cidadeOrigem;
    }

    public function setCidadeOrigem($cidadeOrigem) {
        $this->cidadeOrigem = $cidadeOrigem;
    }

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

}