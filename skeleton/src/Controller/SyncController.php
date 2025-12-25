<?php

namespace App\Controller;

use App\Constants\CbmscConstants;
use App\Service\CalculadorDeAntiguidade;
use App\Service\CalculadorDePontos;
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
        ConversorPlanilhasBombeiro $conversorPlanilhasBombeiro,
        CalculadorDeAntiguidade $calculadorDeAntiguidade
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
                $dadosPlanilhaBrutos = $googleSheetsService->getSheetData($sheetId, CbmscConstants::PLANILHA_HORARIOS_COLUNA_DATA_INITIAL . ":" . CbmscConstants::PLANILHA_HORARIOS_COLUNA_DATA_FINAL);
                $bombeiros = $conversorPlanilhasBombeiro->convertePlanilhaParaObjetosDeBombeiros($dadosPlanilhaBrutos);


                $servico = new CalculadorDePontos($calculadorDeAntiguidade);
                foreach ($bombeiros as $bombeiro) {
                    $servico->adicionarBombeiro($bombeiro);
                }
                $todosOsTurnos = $servico->distribuirTurnosParaMes();

                /**
                 * A linha abaixo apenas converte respostas para PME preliminar. A chamada seguinte gera a sugestão do algoritmo de distribuição de turnos.
                 * TODO: Receber qual algoritmo queremos rodar no frontend.
                 */
                $dadosPlanilhaProcessados = $conversorPlanilhasBombeiro->converterBombeirosParaPlanilha($bombeiros);
                // $dadosPlanilhaProcessados = $conversorPlanilhasBombeiro->converterTurnosDisponibilidadeParaPlanilha($todosOsTurnos, $bombeiros);

                if ( count($dadosPlanilhaProcessados) == 0 ) {
                    $this->addFlash('error', 'Nenhum dado foi processado. Por favor, verifique se os IDs das planilhas estão corretos ou se há dados nas planilhas.');
                    return $this->render('home.html.twig', [
                        'sheetId' => $sheetId,
                        'sheetIdB' => $sheetIdB
                    ]);
                }

                $numberOfLines = count($bombeiros);
                $spreadsheetRange = CbmscConstants::PLANILHA_PME_COLUNA_NOMES . CbmscConstants::PLANILHA_PME_PRIMEIRA_LINHA_NOMES . 
                    ":" . CbmscConstants::PLANILHA_PME_COLUNA_DIA_31 . 
                    (CbmscConstants::PLANILHA_PME_PRIMEIRA_LINHA_NOMES + $numberOfLines);

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
