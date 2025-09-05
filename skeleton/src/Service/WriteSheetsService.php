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

    public function estruturarDados(array $result): array
    {
        $dadosEstruturados = [];
        $bombeiros = [];

        foreach ($result as $linha) 
        {    
            $nome = $linha[1] ?? '';
            $cpf = $linha[2] ?? '';

            if (!$nome) {
                continue; 
            }

            // criamos o array caso esse não existir ainda
            if (!isset($bombeiros[$nome])) {
                $bombeiros[$nome] = array_fill(0, 33, ""); 
                $bombeiros[$nome][0] = $nome;
            }

            // procura os turnos
            for ($dia = 1; $dia <= 33; $dia++) {
                $indiceTurno = $dia + 1; // começa no 2 por conta da estrutura da tabela final

                if (isset($linha[$indiceTurno]) && !empty($linha[$indiceTurno])) {
                    $turno = $linha[$indiceTurno];

                    $mapeamentoTurno = match ($turno) {
                        "Integral" => "I",
                        "Diurno" => "D",
                        "Noturno" => "N",
                        default => "",
                    };

                    $bombeiros[$nome][$dia] = $mapeamentoTurno;
                }
            }

            $bombeiros[$nome][1] = $cpf;
        }

        // array associativo em lista de array
        foreach ($bombeiros as $linha) {
            $dadosEstruturados[] = $linha;
        }

        return $dadosEstruturados;
    }


    public function convertePlanilhaParaObjetosDeBombeiros(array $result): array
    {
        $bombeiros = [];

        foreach ($result as $linha) 
        {
            $nome = $linha[CbmscConstants::COLUNA_NOME] ?? '';
            $cpf = $linha[CbmscConstants::COLUNA_CPF] ?? '';
            $carteiraAmbulancia = $linha[CbmscConstants::COLUNA_CARTEIRA_DE_AMBULANCIA] ?? '';

            $bombeiro = new Bombeiro($nome, $cpf, $carteiraAmbulancia);

            if (!$bombeiro->getNome()) {
                continue; 
            }

            // procura os turnos
            for ($dia = 1; $dia <= 31; $dia++) {
                // -1 porque estamos começando com índice 1 e não 0 (para facilitar legibilidade - dias 1 até 31 em vez de dias 0 até 30)
                $indiceTurno = CbmscConstants::COLUNA_DIA_1 -1 + $dia;

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
}
