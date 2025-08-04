<?php

// Aqui vai a inclusão de outros arquivos necessários, se houver

class Bombeiro {
    // Atributos
    private $nome;
    private $cpf;
    private $numeroCelular;
    private $antiguidade = 0;
    private $carteiraAmbulancia = false;
    private $pontuacao = 0;
    private $cidadeOrigem;
    private $diasQueQuerServico = [];

    // Construtor
    public function __construct($nome, $cpf, $numeroCelular, $antiguidade, $carteiraAmbulancia, $cidadeOrigem){
        $this->setNome($nome);
        $this->setCpf($cpf);
        $this->setNumeroCelular($numeroCelular);
        $this->setAntiguidade($antiguidade);
        $this->setCarteiraAmbulancia($carteiraAmbulancia);
        $this->setCidadeOrigem($cidadeOrigem);

        //

        switch ($antiguidade) {
            case $antiguidade < 2:
                $this->setPontuacao(5);
                break;
            case $antiguidade > 2 && $antiguidade < 5:
                $this->setPontuacao(10);
                break;
            case $antiguidade >= 5:
                $this->setPontuacao(15);
                break;
            case $antiguidade < 10:
                $this->setPontuacao(20);
                break;
        }

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
        }

        

    }

    // Métodos
    public function exibirDados() {
        return "Nome: {$this->getNome()}, CPF: {$this->getCpf()}, Celular: {$this->getNumeroCelular()}, Antiguidade: {$this->getAntiguidade()}, Carteira Ambulância: {$this->getCarteiraAmbulancia()}";
    }

    public function tirarServico() {
        return "O bombeiro {$this->getNome()} está de serviço."; // Exemplo do que podemos fazer
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

    public function getNumeroCelular() {
        return $this->numeroCelular;
    }

    public function setNumeroCelular($numeroCelular) {
        $this->numeroCelular = $numeroCelular;
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

}