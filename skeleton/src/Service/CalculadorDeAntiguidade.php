<?php

namespace App\Service;

use App\Entity\Bombeiro;

class CalculadorDeAntiguidade
{
    private array $antiguidadeData = [];

    /**
     * Injeta os dados de antiguidade carregados da planilha do Google Sheets.
     * Formato esperado: array de arrays onde [0] = CPF, [1] = posição de antiguidade
     */
    public function setAntiguidadeData(array $data): void
    {
        $this->antiguidadeData = $data;
    }

    /**
     * A antiguidade menor é a mais alta, aqui preciamos normalizar para inveter esse valor.
     */
    private function normalizaAntiguidade(int $antiguidade): int {
        return 100 - $antiguidade;
    }

    /**
     * Obtem a antiguidade normalizada para um bombeiro, isso é,
     * fazemos a conversão do cálculo de antiguidade extenrno para a lógica interna do sistema
     */
    public function getAntiguidade(Bombeiro $bombeiro): int
    {
        foreach ($this->antiguidadeData as $antiguidade) {
            if (! isset($antiguidade[0]) || ! isset($antiguidade[1]) ) {
                continue;
            }

            if ($bombeiro->getCpf() == $antiguidade[0]) {
                return $this->normalizaAntiguidade(intval($antiguidade[1]));
            }
        }
        
        return 0;
    }

}
