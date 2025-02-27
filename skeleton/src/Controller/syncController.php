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
        if ($request->isMethod('POST')) {
            $sheetId = $request->request->get('sheetId');
            $sheetIdB = $request->request->get('sheetIdB');

            if (!$sheetId || !$sheetIdB) {
                $this->addFlash('error', 'Os IDs das planilhas são obrigatórios!');
                return $this->redirectToRoute('home_page');
            }

            try {
                $credentialsPath = $_ENV['GOOGLE_AUTH_CONFIG'];

                $result = $googleSheetsService->getSheetData($sheetId, "A1:C100");
                
                $dadosEstruturados = $writeSheetsService->estruturarDados($result);

                $writeSheetsService->configureClient($credentialsPath, $sheetIdB);
                $writeSheetsService->appendData("A13:AH13", $dadosEstruturados);

                $this->addFlash('success', 'Dados organizados e escritos na planilha B com sucesso!');
            } catch (\Exception $e) {
                $this->addFlash('Erro', 'Erro ao sincronizar planilhas: ' . $e->getMessage());
            }

            return $this->redirectToRoute('home_page');
        }

        return $this->render('home.html.twig');
    }
}
