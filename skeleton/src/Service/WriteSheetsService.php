<?php

namespace App\Service;

use App\Constants\CbmscConstants;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use App\Entity\Bombeiro;
use App\Entity\Disponibilidade;

class WriteSheetsService
{
    private ?Sheets $service = null;
    private string $sheetIdB; 

    public function configureClient(string $credentialsPath, string $sheetIdB): void
    {
        $this->sheetIdB = $sheetIdB; 

        $client = new Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Sheets::SPREADSHEETS);
        $this->service = new Sheets($client);
    }

    public function appendData(string $range, array $values): void
    {
        if ($this->service === null) {
            throw new \Exception("O cliente do Google Sheets não foi configurado. Chame configureClient() primeiro.");
        }   

        $body = new ValueRange([
            'values' => $values 
        ]);

        $params = ['valueInputOption' => 'RAW'];

        $this->service->spreadsheets_values->append(
            $this->sheetIdB, 
            $range,
            $body,
            $params
        );

    }

    public function updateData(string $range, array $values): void
    {
        if ($this->service === null) {
            throw new \Exception("O cliente do Google Sheets não foi configurado. Chame configureClient() primeiro.");
        }   

        $body = new ValueRange([
            'values' => $values 
        ]);

        $params = ['valueInputOption' => 'RAW'];

        $this->service->spreadsheets_values->update(
            $this->sheetIdB, 
            $range,
            $body,
            $params
        );

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
