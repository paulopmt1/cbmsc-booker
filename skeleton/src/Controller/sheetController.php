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

    #[Route('/sheets', name:'get_sheets')]
    public function getSheetsData(): JsonResponse
    {   
        // inserir id real
        $sheetId = '1eImum6WiihpCqGaDISo-RWj-q0QPfy64PLIJj50dT5M';

        // inserir nome se necessário
        $sheetName = '';

        $data = $this->googleSheetsService->getSheetData($sheetId, $sheetName);

        return $this->json($data);
    }
}