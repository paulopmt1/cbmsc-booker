<?php

namespace App\Tests\FiremanBundle\Service;

use App\FiremanBundle\Entity\FiremanEntity;
use App\FiremanBundle\Service\SeniorityCalculator;
use PHPUnit\Framework\TestCase;

class SeniorityCalculatorTest extends TestCase
{
    public function testMatchesFormattedSpreadsheetCpfWithNormalizedEntityCpf(): void
    {
        $calculator = new SeniorityCalculator();
        $calculator->setAntiguidadeData([
            ['123.456.789-01', '15'],
        ]);

        $firefighter = new FiremanEntity('Bombeiro', '12345678901', false);

        self::assertSame(85, $calculator->getAntiguidade($firefighter));
    }

    public function testReturnsZeroWhenCpfIsNotPresentInSeniorityData(): void
    {
        $calculator = new SeniorityCalculator();
        $calculator->setAntiguidadeData([
            ['123.456.789-01', '15'],
        ]);

        $firefighter = new FiremanEntity('Bombeiro', '10987654321', false);

        self::assertSame(0, $calculator->getAntiguidade($firefighter));
    }
}
