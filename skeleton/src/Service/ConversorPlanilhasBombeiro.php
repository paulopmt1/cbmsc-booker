<?php

namespace App\Service;

use App\Constants\CbmscConstants;
use App\Entity\Bombeiro;
use App\Entity\Disponibilidade;

class ConversorPlanilhasBombeiro
{
    public function __construct(
        private readonly GoogleSheetsService $googleSheetsService
    ) {
    }


    public function convertePlanilhaParaObjetosDeBombeiros(array $result): array
    {
        $bombeiros = [];

        foreach ($result as $linha) 
        {
            $nome = $linha[CbmscConstants::PLANILHA_HORARIOS_COLUNA_NOME] ?? '';
            $cpf = $linha[CbmscConstants::PLANILHA_HORARIOS_COLUNA_CPF] ?? '';
            $carteiraAmbulancia = $linha[CbmscConstants::PLANILHA_HORARIOS_COLUNA_CARTEIRA_DE_AMBULANCIA] ?? '';

            $bombeiro = new Bombeiro($nome, $cpf, $carteiraAmbulancia);

            if (!$bombeiro->getNome()) {
                continue; 
            }

            // procura os turnos
            for ($dia = 1; $dia <= 31; $dia++) {
                // -1 porque estamos começando com índice 1 e não 0 (para facilitar legibilidade - dias 1 até 31 em vez de dias 0 até 30)
                $indiceTurno = CbmscConstants::PLANILHA_HORARIOS_COLUNA_DIA_1 -1 + $dia;

                if (isset($linha[$indiceTurno]) && !empty($linha[$indiceTurno])) {
                    $turno = strtoupper($linha[$indiceTurno]);

                    if (!in_array($turno, CbmscConstants::getTurnosValidos())) {
                        continue;
                    }

                    $disponibilidade = new Disponibilidade($dia, $turno);
                    $bombeiro->adicionarDisponibilidade($disponibilidade);
                }
            }

            $bombeiros[] = $bombeiro;
        }

        return $bombeiros;
    }

    public function converterBombeirosParaPlanilha(array $bombeiros): array
    {
        $planilha = [];

        /**
         * @var Bombeiro $bombeiro
         */
        foreach ($bombeiros as $bombeiro) {
            $bombeiroArray = [];
            $bombeiroArray[CbmscConstants::PLANILHA_PME_COLUNA_NOME] = $bombeiro->getNome();
            $bombeiroArray[CbmscConstants::PLANILHA_PME_COLUNA_CPF] = $bombeiro->getCpf();
            $bombeiroArray[CbmscConstants::PLANILHA_PME_COLUNA_CARTEIRA_DE_AMBULANCIA] = $bombeiro->getCarteiraAmbulancia();

            for ($dia = 1; $dia <= 31; $dia++) {
                // -1 porque começamos com o indice do dia 1
                $correcaoIndice = -1;
                $indiceTurno = CbmscConstants::PLANILHA_PME_COLUNA_DIA_1 + $dia + $correcaoIndice;
                $bombeiroArray[$indiceTurno] = 
                    $bombeiro->getDisponibilidade($dia) ? 
                        $this->converterTurnoParaLetra($bombeiro->getDisponibilidade($dia)->getTurno()) : 
                        '';
            }

            $planilha[] = $bombeiroArray;
        }

        return $planilha;
    }

    private function converterTurnoParaLetra(string $turno): string
    {
        return match ($turno) {
            CbmscConstants::TURNO_INTEGRAL => 'I',
            CbmscConstants::TURNO_DIURNO => 'D',
            CbmscConstants::TURNO_NOTURNO => 'N',
        };
    }
}
