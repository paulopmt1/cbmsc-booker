<?php

namespace App\Service;

use Google\Client;
use Google\Service\Sheets;

class ConfigureClientService {

    private Sheets $service;

    public function __construct(string $credentialsPath) {
        $this->service = $this->createClient($credentialsPath);
    }

    private function createClient(string $credentialsPath) {
        $client = new Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Sheets::SPREADSHEETS);
        return new Sheets($client);
    }

    public function getService(): Sheets {
        return $this->service;
    }
}