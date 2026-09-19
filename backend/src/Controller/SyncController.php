<?php

namespace App\Controller;

use App\Constants\CbmscConstants;
use App\FiremanBundle\Service\FiremanSpreadsheetConverter;
use App\FiremanBundle\Service\SeniorityCalculator;
use App\GoogleSheetsBundle\Service\GoogleSheetsService;
use App\ShiftBundle\Service\ShiftAllocator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SyncController extends AbstractController
{
    #[Route('/', name: 'home_page', methods: ['GET', 'POST'])]
    public function handleSync(
        Request $request,
        GoogleSheetsService $googleSheetsService,
        FiremanSpreadsheetConverter $firemanSpreadsheetConverter,
        SeniorityCalculator $seniorityCalculator,
        ParameterBagInterface $parameterBag
    ): Response {
        
        if ($request->isMethod('POST'))
        {
            $scheduleSelectionSheetId = $request->request->get('sheetIdEscolhaHorarios');
            $preliminarySheetId = $request->request->get('sheetIdPreliminar');
            $senioritySheetId = $request->request->get('sheetIdAntiguidade');
            $dailyQuotas = $request->request->get('cotasPorDia');
            $driverDaysValue = $request->request->get('diasMotoristaHidden');
            $processingType = $request->request->get('tipoProcessamento') ? 'algoritmo' : 'simples';

            if (!$scheduleSelectionSheetId || !$preliminarySheetId)
            {
                $this->addFlash('error', 'Os IDs corretos das planilhas são necessários para realizar a sincronização!');
            }

            // Validate dailyQuotas
            if ($dailyQuotas === null || $dailyQuotas === '') {
                $this->addFlash('error', 'O campo "Cotas por dia" é obrigatório! 2.5 é o padrão.');
            }

            $dailyQuotasValue = floatval($dailyQuotas);
            if ($dailyQuotasValue <= 0 || !is_numeric($dailyQuotas)) {
                $this->addFlash('error', 'O campo "Cotas por dia" deve ser um número positivo maior que zero!');
                return $this->render('home.html.twig', [
                    'scheduleSelectionSheetId' => $scheduleSelectionSheetId,
                    'preliminarySheetId' => $preliminarySheetId,
                    'senioritySheetId' => $senioritySheetId,
                    'dailyQuotas' => $dailyQuotas,
                    'driverDaysValue' => $driverDaysValue,
                    'processingType' => $processingType
                ]);
            }

            $selectedDays = $this->parseSelectedDays($driverDaysValue);

            try 
            {
                // Validate that the sheet title contains "escolha de horários" to prevent miscopying
                $scheduleSelectionTitle = $googleSheetsService->getSpreadsheetTitle($scheduleSelectionSheetId);
                if (stripos($scheduleSelectionTitle, 'escolha de horários') === false && stripos($scheduleSelectionTitle, 'escolha de horarios') === false) {
                    $this->addFlash('error', 'A planilha de escolha de horários deve conter "escolha de horários" no título. Título encontrado: "' . $scheduleSelectionTitle . '"');
                    return $this->render('home.html.twig', [
                        'scheduleSelectionSheetId' => $scheduleSelectionSheetId,
                        'preliminarySheetId' => $preliminarySheetId,
                        'senioritySheetId' => $senioritySheetId,
                        'dailyQuotas' => $dailyQuotas,
                        'driverDaysValue' => $driverDaysValue,
                        'processingType' => $processingType
                    ]);
                }

                // Validate that the sheet title contains "PME Preliminar" to prevent miscopying
                $preliminaryTitle = $googleSheetsService->getSpreadsheetTitle($preliminarySheetId);
                if (stripos($preliminaryTitle, 'PME Preliminar') === false) {
                    $this->addFlash('error', 'A planilha preliminar deve conter "PME Preliminar" no título. Título encontrado: "' . $preliminaryTitle . '"');
                    return $this->render('home.html.twig', [
                        'scheduleSelectionSheetId' => $scheduleSelectionSheetId,
                        'preliminarySheetId' => $preliminarySheetId,
                        'senioritySheetId' => $senioritySheetId,
                        'dailyQuotas' => $dailyQuotas,
                        'driverDaysValue' => $driverDaysValue,
                        'processingType' => $processingType
                    ]);
                }

                $rawSpreadsheetData = $googleSheetsService->getSheetData($scheduleSelectionSheetId, CbmscConstants::PLANILHA_HORARIOS_COLUNA_DATA_INITIAL . ":" . CbmscConstants::PLANILHA_HORARIOS_COLUNA_DATA_FINAL);
                $firefighters = $firemanSpreadsheetConverter->convertePlanilhaParaObjetosDeBombeiros($rawSpreadsheetData);

                // Load antiguidade data from spreadsheet (A2:B to skip header row)
                if ($senioritySheetId) {
                    // Validate that the sheet title contains "antiguidade" to prevent miscopying
                    $seniorityTitle = $googleSheetsService->getSpreadsheetTitle($senioritySheetId);
                    if (stripos($seniorityTitle, 'antiguidade') === false) {
                        $this->addFlash('error', 'A planilha de antiguidade deve conter "antiguidade" no título. Título encontrado: "' . $seniorityTitle . '"');
                        return $this->render('home.html.twig', [
                            'scheduleSelectionSheetId' => $scheduleSelectionSheetId,
                            'preliminarySheetId' => $preliminarySheetId,
                            'senioritySheetId' => $senioritySheetId,
                            'dailyQuotas' => $dailyQuotas,
                            'driverDaysValue' => $driverDaysValue,
                            'processingType' => $processingType
                        ]);
                    }
                    
                    $seniorityData = $googleSheetsService->getSheetData($senioritySheetId, 'A2:B');
                    $seniorityCalculator->setAntiguidadeData($seniorityData);
                }

                // Convert dailyQuotas to hours (1 quota = 24 hours)
                $dailyHours = $dailyQuotasValue * 24;

                $priorityFirefighterCpf = $parameterBag->get('cpf_do_querubin');
                $allocatorService = new ShiftAllocator($seniorityCalculator, $priorityFirefighterCpf);
                foreach ($firefighters as $firefighter) {
                    $allocatorService->adicionarBombeiro($firefighter);
                }
                $allShifts = $allocatorService->distribuirTurnosParaMes($dailyHours, $selectedDays);

                // Escolha do tipo de processamento baseado na seleção do usuário
                if ($processingType === 'simples') {
                    // Conversão simples: apenas converte respostas para PME preliminar
                    $processedSpreadsheetData = $firemanSpreadsheetConverter->converterBombeirosParaPlanilha($firefighters);
                } else {
                    // Processar com Algoritmo: gera a sugestão do algoritmo de distribuição de turnos
                    $processedSpreadsheetData = $firemanSpreadsheetConverter->converterTurnosDisponibilidadeParaPlanilha($allShifts, $firefighters);
                }

                if ( count($processedSpreadsheetData) == 0 ) {
                    $this->addFlash('error', 'Nenhum dado foi processado. Por favor, verifique se os IDs das planilhas estão corretos ou se há dados nas planilhas.');
                    return $this->render('home.html.twig', [
                        'scheduleSelectionSheetId' => $scheduleSelectionSheetId,
                        'preliminarySheetId' => $preliminarySheetId,
                        'senioritySheetId' => $senioritySheetId,
                        'dailyQuotas' => $dailyQuotas,
                        'driverDaysValue' => $driverDaysValue,
                        'processingType' => $processingType
                    ]);
                }

                $numberOfLines = count($firefighters);
                $spreadsheetRange = CbmscConstants::PLANILHA_PME_COLUNA_NOMES . CbmscConstants::PLANILHA_PME_PRIMEIRA_LINHA_NOMES . 
                    ":" . CbmscConstants::PLANILHA_PME_COLUNA_DIA_31 . 
                    (CbmscConstants::PLANILHA_PME_PRIMEIRA_LINHA_NOMES + $numberOfLines);

                $googleSheetsService->updateData($preliminarySheetId, $spreadsheetRange, $processedSpreadsheetData);

                $this->addFlash('success', 'Dados sincronizados com sucesso!');
                return $this->render('home.html.twig', [
                    'scheduleSelectionSheetId' => $scheduleSelectionSheetId,
                    'preliminarySheetId' => $preliminarySheetId,
                    'senioritySheetId' => $senioritySheetId,
                    'dailyQuotas' => $dailyQuotas,
                    'driverDaysValue' => $driverDaysValue,
                    'processingType' => $processingType
                ]);
            }

            catch (\Exception $e)
            {
                $this->addFlash('error', 'Erro ao sincronizar planilhas. Verifique se os IDs das planilhas estão corretos e tente novamente.');
                $this->addFlash('dev_error', $e->getMessage());
                return $this->render('home.html.twig', [
                    'scheduleSelectionSheetId' => $scheduleSelectionSheetId,
                    'preliminarySheetId' => $preliminarySheetId,
                    'senioritySheetId' => $senioritySheetId,
                    'dailyQuotas' => $dailyQuotas,
                    'driverDaysValue' => $driverDaysValue,
                    'processingType' => $processingType
                ]);
            }
        }

        return $this->render('home.html.twig');
    }

    /**
     * Parse selected days from comma-separated YYYY-MM-DD dates and return array of day numbers (1-31)
     * 
     * @param string $driverDaysValue Comma-separated dates in YYYY-MM-DD format
     * @return array|null Array of day numbers (1-31) or null if no days selected
     */
    private function parseSelectedDays(string $driverDaysValue): ?array
    {
        if (empty($driverDaysValue)) {
            return null;
        }

        $dates = explode(',', $driverDaysValue);
        $days = [];

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
                    $days[] = $day;
                }
            }
        }

        return !empty($days) ? array_unique($days) : null;
    }
}
