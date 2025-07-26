<?php

namespace App\Controller;

use App\Service\GoogleSheetsService;
use App\Service\WriteSheetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SyncController extends AbstractController
{
    #[Route('/', name: 'home_page', methods: ['GET', 'POST'])]
    public function handleSync(
        Request $request,
        GoogleSheetsService $googleSheetsService,
        WriteSheetsService $writeSheetsService
    ): Response {
        
        if ($request->isMethod('POST'))
        {
            $sheetId = $request->request->get('sheetId');
            $sheetIdB = $request->request->get('sheetIdB');

            if (!$sheetId || !$sheetIdB) 
            {
                $this->addFlash('error', 'Os IDs corretos das planilhas são necessários para realizar a sincronização!');
                return $this->render('home.html.twig', [
                    'sheetId' => $sheetId,
                    'sheetIdB' => $sheetIdB
                ]);
            }

            try 
            {
                $credentialsPath = $_ENV['GOOGLE_AUTH_CONFIG'];

                $result = $googleSheetsService->getSheetData($sheetId, "A1:C100");
                
                $dadosEstruturados = $writeSheetsService->estruturarDados($result);

                $writeSheetsService->configureClient($credentialsPath, $sheetIdB);
                $firstLineForNames = 13;
                $firstColumnForNames = "A";
                $columnForDay31 = "AH";
                $numberOfLines = count($dadosEstruturados);
                $writeSheetsService->updateData($firstColumnForNames . $firstLineForNames . ":" . $columnForDay31 . ($firstLineForNames + $numberOfLines), $dadosEstruturados);

                if (!isset($dadosEstruturados))
                {
                    $this->addFlash('error', 'Ocorreu um erro ao tentarmos sincronizar as planilhas. Por favro, verifique se os IDs das planilhas estão corretos ou se há dados nas planilhas.');     
                    return $this->render('home.html.twig', [
                        'sheetId' => $sheetId,
                        'sheetIdB' => $sheetIdB
                    ]);
                }

                $this->addFlash('success', 'Dados sincronizados com sucesso!');
                return $this->render('home.html.twig', [
                    'sheetId' => $sheetId,
                    'sheetIdB' => $sheetIdB
                ]);
            }

            catch (\Exception $e)
            {
                $this->addFlash('error', 'Erro ao sincronizar planilhas. Verifique se os IDs das planilhas estão corretos e tente novamente.');
                $this->addFlash('dev_error', $e->getMessage());
                return $this->render('home.html.twig', [
                    'sheetId' => $sheetId,
                    'sheetIdB' => $sheetIdB
                ]);
            }
        }

        return $this->render('home.html.twig');
    }
}
