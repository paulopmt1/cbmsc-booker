<?php

namespace App\AvailabilityBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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

    #[ORM\Column(name: 'turno', type: 'string', length: 50)] // turno
    #[Assert\NotBlank(message: 'O turno é obrigatório')]
    #[Assert\Choice(choices: ['noturno', 'integral', 'diurno'], message: 'O turno deve ser noturno, integral ou diurno')]
    private string $shift; // Terá um Emum ou classe para escolher turnos fixos aqui?

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

    public function setShift(ShiftType $shift): AvailabilityEntity
    {
        $this->shift = $shift;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [  ];
    }
}