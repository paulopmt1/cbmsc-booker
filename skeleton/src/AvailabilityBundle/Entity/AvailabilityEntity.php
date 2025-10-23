<?php

namespace App\AvailabilityBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use UserBundle\Entity\CreatedAndUpdatedEntityTrait;
use UserBundle\Entity\UpdatedByUserEntityTrait;

#[ORM\ORM\Table(name: 'disponibilidade', schema: 'db_cbmsc_booker')]
#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
class AvailabilityEntity implements \JsonSerializable
{
    use CreatedAndUpdatedEntityTrait;
    use UpdatedByUserEntityTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: 'string', length: 20, enumType: shiftType::class)]
    private ?ShiftType $shift = null;

    public function jsonSerialize(): array
    {
        return [  ];
    }
}