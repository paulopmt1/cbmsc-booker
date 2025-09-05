<?php

namespace App\Controller;

use App\Constants\CbmscConstants;
use App\Service\GoogleSheetsService;
use App\Service\ConversorPlanilhasBombeiro;
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
        ConversorPlanilhasBombeiro $conversorPlanilhasBombeiro
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
                $dadosPlanilhaBrutos = $googleSheetsService->getSheetData($sheetId, "A2:AI100");
                $bombeiros = $conversorPlanilhasBombeiro->convertePlanilhaParaObjetosDeBombeiros($dadosPlanilhaBrutos);
                $dadosPlanilhaProcessados = $conversorPlanilhasBombeiro->converterBombeirosParaPlanilha($bombeiros);

                if ( count($dadosPlanilhaProcessados) == 0 ) {
                    $this->addFlash('error', 'Nenhum dado foi processado. Por favor, verifique se os IDs das planilhas estão corretos ou se há dados nas planilhas.');
                    return $this->render('home.html.twig', [
                        'sheetId' => $sheetId,
                        'sheetIdB' => $sheetIdB
                    ]);
                }

                $numberOfLines = count($bombeiros);
                $spreadsheetRange = CbmscConstants::PLANILHA_HORARIOS_COLUNA_NOMES . CbmscConstants::PLANILHA_HORARIOS_PRIMEIRA_LINHA_NOMES . 
                    ":" . CbmscConstants::PLANILHA_HORARIOS_COLUNA_DIA_31 . 
                    (CbmscConstants::PLANILHA_HORARIOS_PRIMEIRA_LINHA_NOMES + $numberOfLines);

                $googleSheetsService->updateData($sheetIdB, $spreadsheetRange, $dadosPlanilhaProcessados);

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
