<?php

namespace App\Entity;



class Bombeiro {
    // Atributos
    private $nome;
    private $cpf;
    private $antiguidade = 0;
    private $carteiraAmbulancia = false;
    private $pontuacao = 0;
    private $cidadeOrigem;
    private $dias = [];
    private $mes;
    private $turno; // manhã, tarde, noite, integral(24 horas?)

    // Constantes
    private const CPF_DO_QUERUBIN = 10010010001; // CPF do Querubin
    private const CIDADE_FRAIBURGO = "Fraiburgo";
    private const CIDADE_VIDEIRA = "Videira";
    private const CIDADE_CACADOR = "Caçador";

    // Construtor
    public function __construct($nome, $cpf, $antiguidade, $carteiraAmbulancia, $cidadeOrigem){
        $this->setNome($nome);
        $this->setCpf($cpf);
        $this->setAntiguidade($antiguidade);
        $this->setCarteiraAmbulancia($carteiraAmbulancia);
        $this->setCidadeOrigem($cidadeOrigem);

        //

        if ($cpf == self::CPF_DO_QUERUBIN) {
            $this->setPontuacao(1000); // Exemplo de pontuação especial
        }

        switch ($cidadeOrigem) {
            case self::CIDADE_VIDEIRA:
                $this->pontuacao += 20;
                break;
            case self::CIDADE_FRAIBURGO:
                $this->pontuacao += 15;
                break;
            case self::CIDADE_CACADOR:
                $this->pontuacao += 10;
            default:
                $this->pontuacao += 5; // Pontuação padrão para outras cidades
                break;
        }

        if ($carteiraAmbulancia == true) {
            $this->pontuacao += 10;
        }

    }

    public function calcularAntiguidade($antiguidade) {
        switch ($antiguidade) {
            case $antiguidade < 2:
                $this->pontuacao += 5;
                break;
            case $antiguidade > 2 && $antiguidade < 5:
                $this->pontuacao += 10;
                break;
            case $antiguidade >= 5:
                $this->pontuacao += 15;
                break;
            case $antiguidade < 10:
                $this->pontuacao += 20;
                break;
        }
    }

    // Métodos

    public function adicionarDisponibilidadeServico($mes, $dias, $turno) {

        $this->setDias($dias);
        $this->setMes($mes);
        $this->setTurno($turno);

        $escolha = [];
        $escolha = [$dias, $mes, $turno];

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

    public function getDias() {
        return $this->dias;
    }

    public function setDias($dias) {
        $this->dias = $dias;
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