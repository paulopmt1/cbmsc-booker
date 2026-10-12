<?php

namespace App\Entity;

use App\Constants\CbmscConstants;

class Turno {
    private int $dia;
    private string $turno;
    private bool $turno_integral_decomposto;

    public function __construct(int $dia, string $turno, bool $turno_integral_decomposto = false) {
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
        $this->turno_integral_decomposto = $turno_integral_decomposto;
    }

    public function getDia(): int {
        return $this->dia;
    }

    public function getTurno(): string {
        return $this->turno;
    }

    public function getETurnoIntegralDecomposto() {
        return $this->turno_integral_decomposto;
    }
}
