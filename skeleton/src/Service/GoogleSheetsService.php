<?php

namespace App\Service;

use Exception;
use Google\Client;
use Google\Service\Sheets;

class GoogleSheetsService
{
    private Client $client;
    private Sheets $sheetsService;

    public function configureClient(string $credentialsPath): void
    {
        $client = new Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Sheets::SPREADSHEETS);
        $this->sheetsService = new Sheets($client);
    }
    

    public function getSheetData(string $sheetId, string $range = 'A:Z'): array
    {
        try {
            $response = $this->sheetsService->spreadsheets_values->get(
                $sheetId,
                $range
            );

            $values = $response->getValues();
            
            if (empty($values)) {
                throw new Exception("A planilha está vazia ou o range especificado não contém dados");
            }

            return $values;

        } catch (\Google\Service\Exception $e) {
            $error = json_decode($e->getMessage(), true);
            throw new Exception("Erro da API do Google Sheets: " . ($error['error']['message'] ?? $e->getMessage()));
        } catch (Exception $e) {
            throw new Exception("Erro ao acessar a planilha: " . $e->getMessage());
        }
    }

    public function getSheetInfo(string $sheetId): array
    {
        try {
            $spreadsheet = $this->sheetsService->spreadsheets->get($sheetId);
            
            return [
                'title' => $spreadsheet->getProperties()->getTitle(),
                'sheets' => array_map(function($sheet) {
                    return [
                        'title' => $sheet->getProperties()->getTitle(),
                        'sheetId' => $sheet->getProperties()->getSheetId(),
                        'gridProperties' => [
                            'rowCount' => $sheet->getProperties()->getGridProperties()->getRowCount(),
                            'columnCount' => $sheet->getProperties()->getGridProperties()->getColumnCount(),
                        ]
                    ];
                }, $spreadsheet->getSheets())
            ];

        } catch (\Google\Service\Exception $e) {
            $error = json_decode($e->getMessage(), true);
            throw new Exception("Erro da API do Google Sheets: " . ($error['error']['message'] ?? $e->getMessage()));
        }
    }

    public function getMultipleRanges(string $sheetId, array $ranges): array
    {
        try {
            $response = $this->sheetsService->spreadsheets_values->batchGet(
                $sheetId,
                ['ranges' => $ranges]
            );

            $result = [];
            foreach ($response->getValueRanges() as $index => $valueRange) {
                $result[$ranges[$index]] = $valueRange->getValues() ?? [];
            }

            return $result;

        } catch (\Google\Service\Exception $e) {
            $error = json_decode($e->getMessage(), true);
            throw new Exception("Erro da API do Google Sheets: " . ($error['error']['message'] ?? $e->getMessage()));
        }
    }
}