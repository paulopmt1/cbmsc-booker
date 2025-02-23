<?php

namespace App\Service;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

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

    public function estruturarDados(array $result): array
    {
        $dadosEstruturados = [];

        $bombeiros = [];

        foreach ($result as $linha)
        {
            $nome = $linha[0] ?? '';
            $dia = (int) $linha[1] ?? 0;
            $turno = $linha[2] ?? '';

            if (!$nome || !$dia || !$turno)
            {
                continue;
            }

            if (!isset($bombeiros[$nome]))
            {
                $bombeiros[$nome] = array_fill(0, 32, "");
                $bombeiros[$nome][0] = $nome;
            }

            $mapeamentoTurno = match ($turno)
            {
                "Integral" => "I",
                "Diurno" => "D",
                "Noturno" => "N",
                default => "",
            };

            $coluna = $dia + 2;
            $bombeiros[$nome][$coluna] = $mapeamentoTurno;
        }

        foreach ($bombeiros as $linha)
        {
            $dadosEstruturados[] = $linha;
        }

        return $dadosEstruturados;
    }
}
