<?php

namespace App\Controller\Api;

use App\Service\ScheduleDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/schedule', name: 'api_schedule_')]
class ScheduleController extends AbstractController
{
    private ScheduleDataService $scheduleDataService;

    public function __construct(ScheduleDataService $scheduleDataService)
    {
        $this->scheduleDataService = $scheduleDataService;
    }

    #[Route('/all', name: 'all', methods: ['GET'])]
    public function getAllScheduleData(): JsonResponse
    {
        try {
            $data = $this->scheduleDataService->getAllScheduleData();
            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/day/{day}', name: 'day', methods: ['GET'])]
    public function getScheduleForDay(int $day): JsonResponse
    {
        try {
            $data = $this->scheduleDataService->getScheduleForDay($day);
            
            if (!$data) {
                return $this->json([
                    'success' => false,
                    'error' => 'Day not found'
                ], 404);
            }

            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/unresolved', name: 'unresolved', methods: ['GET'])]
    public function getUnresolvedConflicts(): JsonResponse
    {
        try {
            $data = $this->scheduleDataService->getUnresolvedConflicts();
            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/resolution/{day}', name: 'get_resolution', methods: ['GET'])]
    public function getResolution(int $day): JsonResponse
    {
        try {
            $data = $this->scheduleDataService->getResolution($day);
            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/resolution/{day}', name: 'save_resolution', methods: ['POST'])]
    public function saveResolution(Request $request, int $day): JsonResponse
    {
        try {
            $content = json_decode($request->getContent(), true);
            
            if (!isset($content['selectedPeople']) || !is_array($content['selectedPeople'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'selectedPeople array is required'
                ], 400);
            }

            $resolvedBy = $content['resolvedBy'] ?? 'User';
            
            $resolution = $this->scheduleDataService->saveResolution(
                $day,
                $content['selectedPeople'],
                $resolvedBy
            );

            return $this->json([
                'success' => true,
                'data' => $resolution,
                'message' => 'Resolution saved successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/resolutions', name: 'all_resolutions', methods: ['GET'])]
    public function getAllResolutions(): JsonResponse
    {
        try {
            $data = $this->scheduleDataService->getAllResolutions();
            return $this->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/resolutions/clear', name: 'clear_resolutions', methods: ['DELETE'])]
    public function clearAllResolutions(): JsonResponse
    {
        try {
            $this->scheduleDataService->clearAllResolutions();
            return $this->json([
                'success' => true,
                'message' => 'All resolutions cleared successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/regenerate', name: 'regenerate', methods: ['POST'])]
    public function regenerateData(): JsonResponse
    {
        try {
            $data = $this->scheduleDataService->generateRandomScheduleData();
            $this->scheduleDataService->saveScheduleData($data);
            
            return $this->json([
                'success' => true,
                'data' => $data,
                'message' => 'Schedule data regenerated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 