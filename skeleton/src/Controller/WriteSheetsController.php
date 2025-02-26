<?php

namespace App\Controller;

use App\Service\WriteSheetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WriteSheetsController extends AbstractController
{
    #[Route('/write-sheet/{sheetIdB}', name:'write_sheets')]
    public function escreverPlanilha(WriteSheetsService $writeSheetsService, string $sheetIdB): Response
    {
        
        $credentialsPath = $_ENV['GOOGLE_AUTH_CONFIG'];

        $writeSheetsService->configureClient($credentialsPath, $sheetIdB);      

        $dados = [
            ['teste', 'teste', 'teste', 'teste']
             
        ];  

        $writeSheetsService->appendData('D13:L13', $dados);

        return new Response("Dados adicionados à planilha: " . $sheetIdB);
    }
}

