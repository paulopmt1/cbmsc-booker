<?php

namespace App\ShiftBundle\Entity;

use App\Constants\CbmscConstants;
use App\Entity\BaseEntityInterface;
use App\FiremanBundle\Entity\FiremanEntity;
use App\ShiftBundle\Repository\ShiftRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'turno')]
#[ORM\UniqueConstraint(name: 'uniq_turno_bombeiro_data', columns: ['bombeiro_id', 'ano', 'mes', 'dia'])]
#[ORM\Entity(repositoryClass: ShiftRepository::class)]
class ShiftEntity implements BaseEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'dia', type: 'smallint')]
    #[Assert\Range(min: 1, max: 31)]
    private int $day = 0;

    #[ORM\Column(name: 'mes', type: 'smallint')]
    #[Assert\Range(min: 1, max: 12)]
    private int $month = 0;

    #[ORM\Column(name: 'ano', type: 'smallint')]
    #[Assert\Positive]
    private int $year = 0;

    #[ORM\Column(name: 'turno', type: 'string', length: 10)]
    #[Assert\Choice(callback: [CbmscConstants::class, 'getTurnosValidos'])]
    private string $shift = '';

    #[ORM\Column(name: 'turno_integral_decomposto', type: 'boolean', options: ['default' => false])]
    private bool $decomposedFullShift = false;

    #[ORM\ManyToOne(targetEntity: FiremanEntity::class)]
    #[ORM\JoinColumn(name: 'bombeiro_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?FiremanEntity $fireman = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getDay(): int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function setMonth(int $month): self
    {
        $this->month = $month;

        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getShift(): string
    {
        return $this->shift;
    }

    public function setShift(string $shift): self
    {
        $this->shift = $shift;

        return $this;
    }

    public function isDecomposedFullShift(): bool
    {
        return $this->decomposedFullShift;
    }

    public function setDecomposedFullShift(bool $decomposedFullShift): self
    {
        $this->decomposedFullShift = $decomposedFullShift;

        return $this;
    }

    public function getFireman(): ?FiremanEntity
    {
        return $this->fireman;
    }

    public function setFireman(FiremanEntity $fireman): self
    {
        $this->fireman = $fireman;

        return $this;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'day' => $this->day,
            'month' => $this->month,
            'year' => $this->year,
            'shift' => $this->shift,
            'decomposedFullShift' => $this->decomposedFullShift,
            'firemanId' => $this->fireman?->getId(),
        ];
    }
}
