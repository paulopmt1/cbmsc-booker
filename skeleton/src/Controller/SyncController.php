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
            $cotasPorDia = $request->request->get('cotasPorDia');
            $diasMotoristaHidden = $request->request->get('diasMotoristaHidden');
            $tipoProcessamento = $request->request->get('tipoProcessamento') ? 'algoritmo' : 'simples';

            if (!$sheetId || !$sheetIdB) 
            {
                $this->addFlash('error', 'Os IDs corretos das planilhas são necessários para realizar a sincronização!');
            }

            // Validate cotasPorDia
            if ($cotasPorDia === null || $cotasPorDia === '') {
                $this->addFlash('error', 'O campo "Cotas por dia" é obrigatório! 2.5 é o padrão.');
            }

            $cotasPorDiaFloat = floatval($cotasPorDia);
            if ($cotasPorDiaFloat <= 0 || !is_numeric($cotasPorDia)) {
                $this->addFlash('error', 'O campo "Cotas por dia" deve ser um número positivo maior que zero!');
                return $this->render('home.html.twig', [
                    'sheetId' => $sheetId,
                    'sheetIdB' => $sheetIdB,
                    'cotasPorDia' => $cotasPorDia,
                    'diasMotorista' => $diasMotoristaHidden,
                    'tipoProcessamento' => $tipoProcessamento
                ]);
            }

            $diasSelecionados = $this->parseSelectedDays($diasMotoristaHidden);
            
            if ($diasSelecionados === null || empty($diasSelecionados)) {
                $this->addFlash('error', 'É necessário selecionar pelo menos um dia válido no campo "Quais dias precisamos de motorista?"!');
                return $this->render('home.html.twig', [
                    'sheetId' => $sheetId,
                    'sheetIdB' => $sheetIdB,
                    'cotasPorDia' => $cotasPorDia,
                    'diasMotorista' => $diasMotoristaHidden,
                    'tipoProcessamento' => $tipoProcessamento
                ]);
            }

            try 
            {
                $dadosPlanilhaBrutos = $googleSheetsService->getSheetData($sheetId, CbmscConstants::PLANILHA_HORARIOS_COLUNA_DATA_INITIAL . ":" . CbmscConstants::PLANILHA_HORARIOS_COLUNA_DATA_FINAL);
                $bombeiros = $conversorPlanilhasBombeiro->convertePlanilhaParaObjetosDeBombeiros($dadosPlanilhaBrutos);

                // Convert cotasPorDia to hours (1 cota = 24 hours)
                $horasPorDia = $cotasPorDiaFloat * 24;

                $servico = new CalculadorDePontos($calculadorDeAntiguidade);
                foreach ($bombeiros as $bombeiro) {
                    $servico->adicionarBombeiro($bombeiro);
                }
                $todosOsTurnos = $servico->distribuirTurnosParaMes($horasPorDia, $diasSelecionados);

                // Escolha do tipo de processamento baseado na seleção do usuário
                if ($tipoProcessamento === 'simples') {
                    // Conversão simples: apenas converte respostas para PME preliminar
                    $dadosPlanilhaProcessados = $conversorPlanilhasBombeiro->converterBombeirosParaPlanilha($bombeiros);
                } else {
                    // Processar com Algoritmo: gera a sugestão do algoritmo de distribuição de turnos
                    $dadosPlanilhaProcessados = $conversorPlanilhasBombeiro->converterTurnosDisponibilidadeParaPlanilha($todosOsTurnos, $bombeiros);
                }

                if ( count($dadosPlanilhaProcessados) == 0 ) {
                    $this->addFlash('error', 'Nenhum dado foi processado. Por favor, verifique se os IDs das planilhas estão corretos ou se há dados nas planilhas.');
                    return $this->render('home.html.twig', [
                        'sheetId' => $sheetId,
                        'sheetIdB' => $sheetIdB,
                        'cotasPorDia' => $cotasPorDia,
                        'diasMotorista' => $diasMotoristaHidden,
                        'tipoProcessamento' => $tipoProcessamento
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
                    'sheetIdB' => $sheetIdB,
                    'cotasPorDia' => $cotasPorDia,
                    'diasMotorista' => $diasMotoristaHidden,
                    'tipoProcessamento' => $tipoProcessamento
                ]);
            }

            catch (\Exception $e)
            {
                $this->addFlash('error', 'Erro ao sincronizar planilhas. Verifique se os IDs das planilhas estão corretos e tente novamente.');
                $this->addFlash('dev_error', $e->getMessage());
                return $this->render('home.html.twig', [
                    'sheetId' => $sheetId,
                    'sheetIdB' => $sheetIdB,
                    'cotasPorDia' => $cotasPorDia,
                    'diasMotorista' => $diasMotoristaHidden,
                    'tipoProcessamento' => $tipoProcessamento
                ]);
            }
        }

        return $this->render('home.html.twig');
    }

    /**
     * Parse selected days from comma-separated YYYY-MM-DD dates and return array of day numbers (1-31)
     * 
     * @param string $diasMotoristaHidden Comma-separated dates in YYYY-MM-DD format
     * @return array|null Array of day numbers (1-31) or null if no days selected
     */
    private function parseSelectedDays(string $diasMotoristaHidden): ?array
    {
        if (empty($diasMotoristaHidden)) {
            return null;
        }

        $dates = explode(',', $diasMotoristaHidden);
        $dias = [];

        foreach ($dates as $dateStr) {
            $dateStr = trim($dateStr);
            if (empty($dateStr)) {
                continue;
            }

            // Parse YYYY-MM-DD format
            $parts = explode('-', $dateStr);
            if (count($parts) === 3) {
                $year = intval($parts[0]);
                $month = intval($parts[1]);
                $day = intval($parts[2]);

                // Validate date
                if (checkdate($month, $day, $year)) {
                    $dias[] = $day;
                }
            }
        }

        return !empty($dias) ? array_unique($dias) : null;
    }
}
