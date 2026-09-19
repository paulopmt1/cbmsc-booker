<?php

namespace App\FiremanBundle\Service;

use App\AvailabilityBundle\Entity\AvailabilityEntity;
use App\Constants\CbmscConstants;
use App\FiremanBundle\Entity\FiremanEntity;

class FiremanSpreadsheetConverter
{

    public function convertePlanilhaParaObjetosDeBombeiros(array $result): array
    {
        $firefighters = [];

        foreach ($result as $row) 
        {
            $name = $row[CbmscConstants::PLANILHA_HORARIOS_COLUNA_NOME] ?? '';
            $cpf = $row[CbmscConstants::PLANILHA_HORARIOS_COLUNA_CPF] ?? '';
            $ambulanceLicense = $row[CbmscConstants::PLANILHA_HORARIOS_COLUNA_CARTEIRA_DE_AMBULANCIA] === 'Sim' ? true : false;

            $firefighter = new FiremanEntity($name, $cpf, $ambulanceLicense);

            if (!$firefighter->getNome()) {
                continue; 
            }

            // procura os turnos
            for ($day = 1; $day <= 31; $day++) {
                // -1 porque estamos começando com índice 1 e não 0 (para facilitar legibilidade - dias 1 até 31 em vez de dias 0 até 30)
                $shiftIndex = CbmscConstants::PLANILHA_HORARIOS_COLUNA_DIA_1 -1 + $day;

                if (isset($row[$shiftIndex]) && !empty($row[$shiftIndex])) {
                    $shift = strtoupper($row[$shiftIndex]);

                    if (!in_array($shift, CbmscConstants::getTurnosValidos())) {
                        continue;
                    }

                    $availability = new AvailabilityEntity($day, $shift);
                    $firefighter->adicionarDisponibilidade($availability);
                }
            }

            $firefighters[] = $firefighter;
        }

        return $firefighters;
    }

    public function converterBombeirosParaPlanilha(array $firefighters): array
    {
        $spreadsheetRows = [];

        /**
         * @var FiremanEntity $firefighter
         */
        foreach ($firefighters as $firefighter) {
            $firefighterRow = [];
            $firefighterRow[CbmscConstants::PLANILHA_PME_COLUNA_NOME] = $firefighter->getNome();
            $firefighterRow[CbmscConstants::PLANILHA_PME_COLUNA_CPF] = $firefighter->getCpf();
            $firefighterRow[CbmscConstants::PLANILHA_PME_COLUNA_CARTEIRA_DE_AMBULANCIA] = $firefighter->getCarteiraAmbulancia();

            for ($day = 1; $day <= 31; $day++) {
                // -1 porque começamos com o indice do dia 1
                $indexOffset = -1;
                $shiftIndex = CbmscConstants::PLANILHA_PME_COLUNA_DIA_1 + $day + $indexOffset;
                $firefighterRow[$shiftIndex] = 
                    $firefighter->getDisponibilidade($day) ? 
                        $this->converterTurnoParaLetra($firefighter->getDisponibilidade($day)->getTurno()) : 
                        '';
            }

            $spreadsheetRows[] = $firefighterRow;
        }

        return $spreadsheetRows;
    }

    public function converterTurnosDisponibilidadeParaPlanilha(array $allShifts, array $firefighters): array
    {
        $spreadsheetRows = [];
        
        /**
         * @var FiremanEntity $firefighter
         */
        foreach ($firefighters as $firefighter) {
            $firefighterRow = [];
            $firefighterRow[CbmscConstants::PLANILHA_PME_COLUNA_NOME] = $firefighter->getNome();
            $firefighterRow[CbmscConstants::PLANILHA_PME_COLUNA_CPF] = $firefighter->getCpf();
            $firefighterRow[CbmscConstants::PLANILHA_PME_COLUNA_CARTEIRA_DE_AMBULANCIA] = $firefighter->getCarteiraAmbulancia();

            for ($day = 1; $day <= 31; $day++) {
                // -1 porque começamos com o indice do dia 1
                $indexOffset = -1;
                $shiftIndex = CbmscConstants::PLANILHA_PME_COLUNA_DIA_1 + $day + $indexOffset;

                $firefighterSelectedForAnyShift = false;
                if (isset($allShifts[$day])) {
                    foreach ($allShifts[$day] as $shift => $firefighters) {
                        if (in_array($firefighter, $firefighters)) {
                            $firefighterSelectedForAnyShift = true;
                            break;
                        }
                    }
                }
                
                $firefighterRow[$shiftIndex] = 
                    $firefighterSelectedForAnyShift ? 
                        $this->converterTurnoParaLetra($firefighter->getDisponibilidade($day)->getTurno()) : 
                        '';
            }

            $spreadsheetRows[] = $firefighterRow;
        }
        
        return $spreadsheetRows;
    }

    private function converterTurnoParaLetra(string $shift): string
    {
        return match ($shift) {
            CbmscConstants::TURNO_INTEGRAL => 'I',
            CbmscConstants::TURNO_DIURNO => 'D',
            CbmscConstants::TURNO_NOTURNO => 'N',
        };
    }
}
