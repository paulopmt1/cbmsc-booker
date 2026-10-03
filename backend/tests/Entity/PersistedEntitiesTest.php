<?php

namespace App\Tests\Entity;

use App\AvailabilityBundle\Entity\AvailabilityEntity;
use App\FiremanBundle\Entity\FiremanEntity;
use App\ShiftBundle\Entity\ShiftEntity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class PersistedEntitiesTest extends TestCase
{
    public function testFiremanSerializesEnglishFields(): void
    {
        $fireman = (new FiremanEntity())
            ->setId(7)
            ->setName('BC Example')
            ->setCpf('12345678901')
            ->setSeniority(4)
            ->setAmbulanceLicense(true)
            ->setOriginCity('Videira');

        self::assertSame([
            'id' => 7,
            'name' => 'BC Example',
            'cpf' => '12345678901',
            'seniority' => 4,
            'ambulanceLicense' => true,
            'originCity' => 'Videira',
        ], $fireman->jsonSerialize());
    }

    public function testRelatedEntitiesSerializeFiremanIdWithoutCycle(): void
    {
        $fireman = (new FiremanEntity())->setId(7);
        $availability = (new AvailabilityEntity())
            ->setDay(3)->setMonth(10)->setYear(2026)
            ->setShift('DIURNO')->setFireman($fireman);
        $shift = (new ShiftEntity())
            ->setDay(3)->setMonth(10)->setYear(2026)
            ->setShift('DIURNO')->setDecomposedFullShift(true)->setFireman($fireman);

        self::assertSame(7, $availability->jsonSerialize()['firemanId']);
        self::assertSame(7, $shift->jsonSerialize()['firemanId']);
        self::assertTrue($shift->jsonSerialize()['decomposedFullShift']);
        self::assertSame($fireman, $availability->getFireman());
        self::assertSame($fireman, $shift->getFireman());
    }

    public function testValidationMatchesRequiredColumnsAndShiftValues(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $fireman = (new FiremanEntity())->setName('BC Example')->setCpf('12345678901');
        $availability = (new AvailabilityEntity())
            ->setDay(3)->setMonth(10)->setYear(2026)
            ->setShift('DIURNO')->setFireman($fireman);

        self::assertCount(0, $validator->validate($fireman));
        self::assertCount(0, $validator->validate($availability));

        $availability->setDay(32)->setShift('INVALIDO');
        self::assertCount(2, $validator->validate($availability));
    }
}
