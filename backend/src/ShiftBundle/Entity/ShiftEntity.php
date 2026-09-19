<?php

namespace App\ShiftBundle\Entity;

use App\Constants\CbmscConstants;
use App\AppBundle\Entity\BaseEntityInterface;
use App\FiremanBundle\Entity\FiremanEntity;
use App\ShiftBundle\Repository\ShiftRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShiftRepository::class)]
#[ORM\Table(name: 'turno')]
#[ORM\UniqueConstraint(
    name: 'uniq_turno_bombeiro_data',
    columns: ['bombeiro_id', 'ano', 'mes', 'dia'],
)]
class ShiftEntity implements BaseEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'acquiredShifts')]
    #[ORM\JoinColumn(name: 'bombeiro_id', nullable: false, onDelete: 'CASCADE')]
    private ?FiremanEntity $firefighter = null;

    #[ORM\Column(name: 'dia', type: 'smallint')]
    private int $day;

    #[ORM\Column(name: 'mes', type: 'smallint')]
    private int $month;

    #[ORM\Column(name: 'ano', type: 'smallint')]
    private int $year;

    #[ORM\Column(name: 'turno', length: 10)]
    private string $shift;

    #[ORM\Column(name: 'turno_integral_decomposto', options: ['default' => false])]
    private bool $splitFullShift;

    public function __construct(
        int $day,
        string $shift,
        bool $splitFullShift = false,
        ?int $month = null,
        ?int $year = null,
    ) {
        $now = new \DateTimeImmutable();
        $this->setTurno($shift);
        $this->day = $day;
        $this->month = $month ?? (int) $now->format('n');
        $this->year = $year ?? (int) $now->format('Y');
        $this->splitFullShift = $splitFullShift;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBombeiro(): ?FiremanEntity
    {
        return $this->firefighter;
    }

    public function setBombeiro(?FiremanEntity $firefighter): self
    {
        $this->firefighter = $firefighter;

        return $this;
    }

    public function getDia(): int
    {
        return $this->day;
    }

    public function getMes(): int
    {
        return $this->month;
    }

    public function getAno(): int
    {
        return $this->year;
    }

    public function getTurno(): string
    {
        return $this->shift;
    }

    public function setTurno(string $shift): self
    {
        if (!in_array($shift, CbmscConstants::getTurnosValidos(), true)) {
            throw new \InvalidArgumentException(sprintf(
                'Turno inválido: "%s". Deve ser um dos seguintes: %s',
                $shift,
                implode(', ', CbmscConstants::getTurnosValidos()),
            ));
        }

        $this->shift = $shift;

        return $this;
    }

    public function getETurnoIntegralDecomposto(): bool
    {
        return $this->splitFullShift;
    }
}
