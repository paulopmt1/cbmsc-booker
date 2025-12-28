<?php

namespace App\Entity;

use App\Constants\CbmscConstants;

class Turno {
    private int $dia;
    private string $turno;

    public function __construct(int $dia, string $turno) {
        $turnosValidos = CbmscConstants::getTurnosValidos();
        
        if (!in_array($turno, $turnosValidos, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Turno inválido: "%s". Deve ser um dos seguintes: %s',
                    $turno,
                    implode(', ', $turnosValidos)
                )
            );
        }
        
        $this->dia = $dia;
        $this->turno = $turno;
    }

    public function getDia(): int {
        return $this->dia;
    }

    public function getTurno(): string {
        return $this->turno;
    }
}
