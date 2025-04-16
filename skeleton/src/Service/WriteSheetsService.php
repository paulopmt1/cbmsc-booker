<?php

namespace App\Service;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class WriteSheetsService
{
    private ConfigureClientService $configureClientService;
    private string $sheetIdB;

    public function __construct(ConfigureClientService $configureClientService) {
        $this->configureClientService = $configureClientService;
    }

    public function appendData(string $range, array $values): void {
    
        if ($this->configureClientService === null) {
            throw new \Exception("O cliente do Google Sheets não foi configurado. Chame configureClient() primeiro.");
        }   

        $body = new ValueRange([
            'values' => $values 
        ]);

        $params = ['valueInputOption' => 'RAW'];

        $this->configureClientService->getService()->spreadsheets_values->append(
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

            if (!$nome) {
                continue; 
            }

            // criamos o array caso esse não existir ainda
            if (!isset($bombeiros[$nome])) {
                $bombeiros[$nome] = array_fill(0, 32, ""); 
                $bombeiros[$nome][0] = $nome; 
            }

            // procura os turnos
            for ($dia = 1; $dia <= 31; $dia++) {
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
        }

        // array associativo em lista de array
        foreach ($bombeiros as $linha) {
            $dadosEstruturados[] = $linha;
        }

        return $dadosEstruturados;
    }

    public function setSheetIdB(string $sheetIdB): void {
        $this->sheetIdB = $sheetIdB;
    }

}