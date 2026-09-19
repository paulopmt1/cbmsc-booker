<?php

namespace App\FiremanBundle\Service;

use App\FiremanBundle\Entity\FiremanEntity;

class SeniorityCalculator
{
    private array $seniorityData = [];

    /**
     * Injeta os dados de antiguidade carregados da planilha do Google Sheets.
     * Formato esperado: array de arrays onde [0] = CPF, [1] = posição de antiguidade
     */
    public function setAntiguidadeData(array $data): void
    {
        $this->seniorityData = $data;
    }

    /**
     * A antiguidade menor é a mais alta, aqui preciamos normalizar para inveter esse valor.
     */
    private function normalizaAntiguidade(int $seniority): int {
        return 100 - $seniority;
    }

    /**
     * Obtem a antiguidade normalizada para um bombeiro, isso é,
     * fazemos a conversão do cálculo de antiguidade extenrno para a lógica interna do sistema
     */
    public function getAntiguidade(FiremanEntity $firefighter): int
    {
        $firefighterCpf = $this->normalizeCpf($firefighter->getCpf());

        foreach ($this->seniorityData as $seniority) {
            if (! isset($seniority[0]) || ! isset($seniority[1]) ) {
                continue;
            }

            if ($firefighterCpf === $this->normalizeCpf((string) $seniority[0])) {
                return $this->normalizaAntiguidade(intval($seniority[1]));
            }
        }
        
        return 0;
    }

    private function normalizeCpf(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf) ?? $cpf;
    }

}
