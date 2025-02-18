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
}
