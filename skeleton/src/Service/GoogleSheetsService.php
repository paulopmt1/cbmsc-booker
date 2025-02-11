<?php

namespace App\Service;

use Exception;

class GoogleSheetsService
{
    public function getSheetData(string $sheetId, string $sheetName): array
    {
        // link da tabela que iremos extrair os dados. Pegamos seu ID
        $url = "https://docs.google.com/spreadsheets/d/{$sheetId}/gviz/tq?tqx=out:json";

        // aqui pegamos os dados da tabela através da função file_get_contents. Da url passada
        $response = file_get_contents($url);

        // removendo prefixos extras add pelo Google
        $response = substr($response, 47, -2);

        // usamos json_decode para transformar o arquivo json obtido, em um array 
        $data = json_decode($response, true);

        // exceção para não gerar um erro caso a planilha esteja vazia 
        if (!isset($data['table']['rows']))
        {
            throw new Exception("A planilha está vazia");
        }

        // processa e organiza os dados em forma de tabela
        $result = [];
        foreach ($data['table']['rows'] as $row)
        {
            $rowData = [];
            foreach ($row['c'] as $cell)
            {
                $rowData[] = $cell['v'] ?? '';
            }

            $result[] = $rowData;
        }

        return $result;

    }
}