<?php

namespace App\Tests\Controller;

use App\GoogleSheetsBundle\Service\GoogleSheetsService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class SyncControllerTest extends KernelTestCase
{
    public function testAlgorithmSyncFlowKeepsSpreadsheetContract(): void
    {
        self::bootKernel();

        $googleSheetsService = $this->createMock(GoogleSheetsService::class);
        $googleSheetsService->method('getSpreadsheetTitle')
            ->willReturnMap([
                ['schedule-sheet', 'Escolha de horários - setembro'],
                ['preliminary-sheet', 'PME Preliminar - setembro'],
                ['seniority-sheet', 'Antiguidade'],
            ]);
        $googleSheetsService->method('getSheetData')
            ->willReturnCallback(static fn (string $sheetId): array => match ($sheetId) {
                'schedule-sheet' => [
                    ['2026-09-01', 'Bombeiro Teste', '123.456.789-01', 'Sim', 'INTEGRAL'],
                ],
                'seniority-sheet' => [
                    ['123.456.789-01', '15'],
                ],
            });
        $googleSheetsService->expects(self::once())
            ->method('updateData')
            ->with(
                'preliminary-sheet',
                'A13:AH14',
                self::callback(static function (array $rows): bool {
                    return count($rows) === 1
                        && $rows[0][0] === 'Bombeiro Teste'
                        && $rows[0][1] === '12345678901'
                        && $rows[0][3] === 'I';
                }),
            );

        static::getContainer()->set(GoogleSheetsService::class, $googleSheetsService);

        $request = Request::create('/', 'POST', [
            'sheetIdEscolhaHorarios' => 'schedule-sheet',
            'sheetIdPreliminar' => 'preliminary-sheet',
            'sheetIdAntiguidade' => 'seniority-sheet',
            'cotasPorDia' => '2.5',
            'diasMotoristaHidden' => '2026-09-01',
            'tipoProcessamento' => 'algoritmo',
        ]);

        $response = self::$kernel->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Dados sincronizados com sucesso!', $response->getContent());

        self::$kernel->terminate($request, $response);
    }
}
