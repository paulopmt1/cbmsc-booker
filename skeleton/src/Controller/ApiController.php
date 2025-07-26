<?php

namespace App\Controller;

use App\Service\GoogleSheetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/api/v1.0", name: "api_v1.0")]
class ApiController extends AbstractController
{
    private GoogleSheetsService $googleSheetsService;

    public function __construct(GoogleSheetsService $googleSheetsService)
    {
        $this->googleSheetsService = $googleSheetsService;
    }

    #[Route("/scales/get-conflicts/{sheetId}", name:'get_conflicts')]
    public function getConflicts(string $sheetId): JsonResponse
    {
        // $result = $this->googleSheetsService->getSheetData($sheetId, $sheetName);
        // Compute conflicts

        $mockedResult = [
            "conflicts" => [
                "date" => "2021-01-01",
                "scales" => [
                    [
                        "scaleName" => "Período Integral",
                        "conflicts" => [
                            [
                                "id" => 1,
                                "date" => "2021-01-01",
                                "status" => "unsolved",
                                "firefighters" => [
                                    [
                                        "id" => 1,
                                        "name" => "BC Roberto",
                                    ],
                                    [
                                        "id" => 2,
                                        "name" => "BC Ana",
                                    ],
                                    [
                                        "id" => 3,
                                        "name" => "BC João",
                                    ]
                                ]
                            ],
                        ]
                    ],
                    [
                        "scaleName" => "Período Noturno",
                        "conflicts" => [
                            [
                                "id" => 2,
                                "date" => "2021-01-01",
                                "status" => "unsolved",
                                "firefighters" => [
                                    [
                                        "id" => 4,
                                        "name" => "BC Léo",
                                    ],
                                    [
                                        "id" => 5,
                                        "name" => "BC Aline",
                                    ],
                                    [
                                        "id" => 6,
                                        "name" => "BC Rafa",
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->json($mockedResult);

    }
}