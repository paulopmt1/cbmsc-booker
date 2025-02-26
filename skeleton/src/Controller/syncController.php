<?php

namespace App\Controller;

use App\Service\GoogleSheetsService;
use App\Service\WriteSheetsService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class syncController extends AbstractController
{
    #[Route('/sync-sheets/{sheetId}/{sheetIdB}', name: 'sync-sheets')]
    public function sincroPlanilhas(
        GoogleSheetsService $googleSheetsService,
        WriteSheetsService $writeSheetsService,
        string $sheetId,
        string $sheetIdB
        ): Response { 
        
        $credentialsPath = $_ENV['GOOGLE_AUTH_CONFIG'];

        $result = $googleSheetsService->getSheetData($sheetId, "A1:C100");
        
        $dadosEstruturados = $writeSheetsService->estruturarDados($result);

        $writeSheetsService->configureClient($credentialsPath, $sheetIdB);
        $writeSheetsService->appendData("A13:AH13", $dadosEstruturados);

        return new Response("Dados organizados e escritos na planilha B!");
    }
}