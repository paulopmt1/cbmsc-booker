<?php

namespace App\Service;

use App\Constants\CbmscConstants;
use App\Entity\Bombeiro;
use App\Entity\Disponibilidade;

class CalculadorDeAntiguidadeService
{
    public function __construct(
        private readonly GoogleSheetsService $googleSheetsService
    ) {
    }

    private function readAntiguidadeFile() {
        if (($handle = fopen('/tmp/antiguidade.csv', 'r')) === false) {
            throw new \Exception('Não foi possível abrir o arquivo de antiguidade!');
        }
        
        $antiguidadeItems = [];

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $antiguidadeItems[] = $data;
        }

        fclose($handle);
        return $antiguidadeItems;
    }

    /**
     * A antiguidade menor é a mais alta, aqui preciamos normalizar para inveter esse valor.
     */
    private function normalizaAntiguidade(array $bombeiros){
        foreach ($bombeiros as $bombeiro) {
            $bombeiro->setAntiguidade(100 - $bombeiro->getAntiguidade());
        }

        return $bombeiros;
    }

    public function definirAntiguidadeBombeiros(array $bombeiros): array
    {
        $antiguidadeItems = $this->readAntiguidadeFile();

        foreach ($bombeiros as $bombeiro) {
            foreach ($antiguidadeItems as $antiguidade) {
                if ($bombeiro->getCpf() == $antiguidade[0]) {
                    $bombeiro->setAntiguidade(intval($antiguidade[1]));
                }
            }
        }

        return $this->normalizaAntiguidade($bombeiros);
    }

}
