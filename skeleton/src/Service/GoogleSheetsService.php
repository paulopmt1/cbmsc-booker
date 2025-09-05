<?php

namespace App\Service;

use Exception;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class GoogleSheetsService
{
    private ?Sheets $sheetsService = null;

    public function __construct(
        private readonly string $credentialsPath
    ) {
        $client = new Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Sheets::SPREADSHEETS);
        $this->sheetsService = new Sheets($client);
    }

    public function getSheetsService(): Sheets
    {
        if ($this->sheetsService === null) {
            throw new Exception("O serviço Google Sheets não foi inicializado corretamente.");
        }
        
        return $this->sheetsService;
    }
    

    public function getSheetData(string $sheetId, string $range = 'A:Z'): array
    {
        try {
            $response = $this->getSheetsService()->spreadsheets_values->get(
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
            $spreadsheet = $this->getSheetsService()->spreadsheets->get($sheetId);
            
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
            $response = $this->getSheetsService()->spreadsheets_values->batchGet(
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

    public function appendData(string $sheetId, string $range, array $values): void
    {
        $body = new ValueRange([
            'values' => $values 
        ]);

        $params = ['valueInputOption' => 'RAW'];

        $this->getSheetsService()->spreadsheets_values->append(
            $sheetId, 
            $range,
            $body,
            $params
        );
    }

    public function updateData(string $sheetId, string $range, array $values): void
    {
        $body = new ValueRange([
            'values' => $values 
        ]);

        $params = ['valueInputOption' => 'RAW'];

        $this->getSheetsService()->spreadsheets_values->update(
            $sheetId, 
            $range,
            $body,
            $params
        );
    }
}