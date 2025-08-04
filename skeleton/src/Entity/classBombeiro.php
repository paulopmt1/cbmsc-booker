<?php



class Bombeiro {
    // Atributos
    private $nome;
    private $cpf;
    private $numeroCelular;
    private $antiguidade;
    private $carteiraAmbulancia = false;

    // Construtor
    public function __construct($nome, $cpf, $numeroCelular, $antiguidade, $carteiraAmbulancia){
        $this->setNome($nome);
        $this->setCpf($cpf);
        $this->setNumeroCelular($numeroCelular);
        $this->setAntiguidade($antiguidade);
        $this->setCarteiraAmbulancia($carteiraAmbulancia);

        // Define a antiguidade

        switch ($antiguidade) {
            case $antiguidade < 2:
                $this->setAntiguidade('Iniciante');
                break;
            case $antiguidade > 2 && $antiguidade < 5:
                $this->setAntiguidade('Intermediário');
                break;
            case $antiguidade >= 5:
                $this->setAntiguidade('Avançado');
                break;
            case $antiguidade < 10:
                $this->setAntiguidade('Antigasso');
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

}