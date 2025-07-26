<?php

namespace App\Service;

class ScheduleDataService
{
    private string $dataFile;
    private string $resolutionsFile;

    public function __construct()
    {
        $this->dataFile = __DIR__ . '/../../var/schedule_data.json';
        $this->resolutionsFile = __DIR__ . '/../../var/schedule_resolutions.json';
        
        // Ensure var directory exists
        $varDir = dirname($this->dataFile);
        if (!is_dir($varDir)) {
            mkdir($varDir, 0755, true);
        }
    }

    public function generateRandomScheduleData(): array
    {
        $days = [];
        $peopleNames = [
            'BC Roberto', 'BC Ana', 'BC Fábio', 'BC Léo', 'BC Aline',
            'BC Maria', 'BC João', 'BC Carla', 'BC Pedro', 'BC Sofia',
            'BC Lucas', 'BC Julia', 'BC Rafael', 'BC Beatriz', 'BC Thiago',
            'BC Camila', 'BC Diego', 'BC Fernanda', 'BC André', 'BC Isabela'
        ];

        for ($day = 1; $day <= 30; $day++) {
            $hasConflict = rand(1, 100) > 30; // 70% chance of having conflicts
            
            if ($hasConflict) {
                $timePeriods = [
                    [
                        'id' => 'integral',
                        'title' => 'Período Integral',
                        'people' => $this->generateRandomPeople($peopleNames, 2, 4)
                    ],
                    [
                        'id' => 'noturno',
                        'title' => 'Período Noturno',
                        'people' => $this->generateRandomPeople($peopleNames, 1, 3)
                    ],
                    [
                        'id' => 'diurno',
                        'title' => 'Período Diurno',
                        'people' => $this->generateRandomPeople($peopleNames, 2, 4)
                    ]
                ];

                $days[] = [
                    'day' => $day,
                    'date' => (new \DateTime("2024-04-{$day}"))->format('Y-m-d'),
                    'hasConflict' => true,
                    'timePeriods' => $timePeriods,
                    'resolved' => false,
                    'resolvedBy' => null,
                    'resolvedAt' => null
                ];
            } else {
                $days[] = [
                    'day' => $day,
                    'date' => (new \DateTime("2024-04-{$day}"))->format('Y-m-d'),
                    'hasConflict' => false,
                    'timePeriods' => [],
                    'resolved' => false,
                    'resolvedBy' => null,
                    'resolvedAt' => null
                ];
            }
        }

        return $days;
    }

    private function generateRandomPeople(array $allPeople, int $minCount, int $maxCount): array
    {
        $count = rand($minCount, $maxCount);
        shuffle($allPeople);
        $selectedPeople = array_slice($allPeople, 0, $count);
        
        return array_map(function($name, $index) {
            return [
                'id' => strtolower(str_replace(' ', '_', $name)) . "_{$index}",
                'name' => $name
            ];
        }, $selectedPeople, array_keys($selectedPeople));
    }

    public function getAllScheduleData(): array
    {
        if (file_exists($this->dataFile)) {
            $data = json_decode(file_get_contents($this->dataFile), true);
            if ($data) {
                return $data;
            }
        }
        
        // Generate new data if file doesn't exist or is invalid
        $data = $this->generateRandomScheduleData();
        $this->saveScheduleData($data);
        return $data;
    }

    public function getScheduleForDay(int $day): ?array
    {
        $allData = $this->getAllScheduleData();
        foreach ($allData as $dayData) {
            if ($dayData['day'] === $day) {
                return $dayData;
            }
        }
        return null;
    }

    public function saveScheduleData(array $data): void
    {
        file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function saveResolution(int $day, array $selectedPeople, string $resolvedBy = 'User'): array
    {
        $resolutions = $this->getAllResolutions();
        
        $resolution = [
            'day' => $day,
            'selectedPeople' => $selectedPeople,
            'resolvedBy' => $resolvedBy,
            'resolvedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            'resolved' => true
        ];
        
        $resolutions[$day] = $resolution;
        
        file_put_contents($this->resolutionsFile, json_encode($resolutions, JSON_PRETTY_PRINT));
        
        return $resolution;
    }

    public function getResolution(int $day): ?array
    {
        $resolutions = $this->getAllResolutions();
        return $resolutions[$day] ?? null;
    }

    public function getAllResolutions(): array
    {
        if (file_exists($this->resolutionsFile)) {
            $data = json_decode(file_get_contents($this->resolutionsFile), true);
            return $data ?: [];
        }
        return [];
    }

    public function getUnresolvedConflicts(): array
    {
        $allData = $this->getAllScheduleData();
        $resolutions = $this->getAllResolutions();
        
        return array_filter($allData, function($day) use ($resolutions) {
            return $day['hasConflict'] && !isset($resolutions[$day['day']]);
        });
    }

    public function clearAllResolutions(): void
    {
        if (file_exists($this->resolutionsFile)) {
            unlink($this->resolutionsFile);
        }
    }
} 