<?php

namespace App\Service;

use App\Entity\Bombeiro;

class CalculadorDeAntiguidade
{
    private function readAntiguidadeFile() {
        // TODO: Mudar para usar banco de dados ou um arquivo melhor.
        if (($handle = fopen(__DIR__ . '/../../config/antiguidade.csv', 'r')) === false) {
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
    private function normalizaAntiguidade(int $antiguidade): int {
        return 100 - $antiguidade;
    }

    /**
     * Obtem a antiguidade normalizada para um bombeiro, isso é,
     * fazemos a conversão do cálculo de antiguidade extenrno para a lógica interna do sistema
     */
    public function getAntiguidade(Bombeiro $bombeiro): int
    {
        $antiguidadeItems = $this->readAntiguidadeFile();

        foreach ($antiguidadeItems as $antiguidade) {
            if (! isset($antiguidade[0]) || ! isset($antiguidade[1]) ) {
                continue;
            }

            if ($bombeiro->getCpf() == $antiguidade[0]) {
                return $this->normalizaAntiguidade(intval($antiguidade[1]));
            }
        }
        
        return 0;
    }

}
