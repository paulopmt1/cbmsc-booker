<?php

namespace App\AvailabilityBundle\Entity;


use App\AvailabilityBundle\Repository\AvailabilityRepository;
use AppBundle\Exception\DomainException;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'disponibilidade', schema: 'db_cbmsc')]
#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
class AvailabilityEntity implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'data', type: 'datetime')]
    private \DateTimeImmutable $date;

    #[ORM\Column(name: 'turno', type: 'string', length: 50)]
    private string $shift;

    public const CITIES = ['videira', 'fraiburgo', 'cacador']; // a cidade do bombeiro deve ser setada na criação do usuário

    public const SHIFTS = ['noturno', 'integral', 'diurno'];

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function getShift(): string
    {
        return $this->shift;
    }

    public function setId(int $id): AvailabilityEntity
    {
        $this->id = $id;

        return $this;
    }

    public function setDate(\DateTimeImmutable $date): AvailabilityEntity
    {
        $this->date = $date;

        return $this;
    }

    public function setShift(string $shift): AvailabilityEntity
    {
        if (!\in_array($shift, self::SHIFTS)) {
            throw new DomainException('Turno inválido');
        }

        $this->shift = $shift;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'shift' => $this->shift,
        ];
    }
}