<?php

namespace App\Controller;

use App\Service\GoogleSheetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class sheetController extends AbstractController
{
    private GoogleSheetsService $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    #[Route("/sheets/{sheetId}", name:'get_sheets')]
    public function getSheetsData(string $sheetId): JsonResponse
    {   
        
        // inserir nome se necessário
        $sheetName = '';

        $result = $this->googleSheetsService->getSheetData($sheetId, $sheetName);

        return $this->json($result);

    }
}