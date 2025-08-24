<?php

namespace App\Entity;

class Disponibilidade {
    // Constantes para enum do turno
    public const TURNO_NOTURNO = "NOTURNO";
    public const TURNO_INTEGRAL = "INTEGRAL";
    public const TURNO_DIURNO = "DIURNO";

    // Atributos
    private int $dia;
    private int $mes;
    private string $turno;

    // Construtor
    public function __construct(int $dia, int $mes, string $turno) {
        $this->setDia($dia);
        $this->setMes($mes);
        $this->setTurno($turno);
    }

    // Getters and Setters
    public function getDia(): int {
        return $this->dia;
    }

    public function setDia(int $dia): void {
        if ($dia < 1 || $dia > 31) {
            throw new \InvalidArgumentException("Dia deve estar entre 1 e 31");
        }
        $this->dia = $dia;
    }

    public function getMes(): int {
        return $this->mes;
    }

    public function setMes(int $mes): void {
        if ($mes < 1 || $mes > 12) {
            throw new \InvalidArgumentException("Mês deve estar entre 1 e 12");
        }
        $this->mes = $mes;
    }

    public function getTurno(): string {
        return $this->turno;
    }

    public function setTurno(string $turno): void {
        $turnosValidos = [self::TURNO_NOTURNO, self::TURNO_INTEGRAL, self::TURNO_DIURNO];
        if (!in_array($turno, $turnosValidos)) {
            throw new \InvalidArgumentException("Turno deve ser um dos valores: " . implode(', ', $turnosValidos));
        }
        $this->turno = $turno;
    }

    // Método para retornar uma representação string da disponibilidade
    public function __toString(): string {
        return "Dia: {$this->dia}, Mês: {$this->mes}, Turno: {$this->turno}";
    }

    // Método para verificar se é igual a outra disponibilidade
    public function equals(Disponibilidade $outra): bool {
        return $this->dia === $outra->getDia() && 
               $this->mes === $outra->getMes() && 
               $this->turno === $outra->getTurno();
    }
}
