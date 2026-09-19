<?php

namespace App\AvailabilityBundle\Entity;

use App\Constants\CbmscConstants;
use App\AvailabilityBundle\Repository\AvailabilityRepository;
use App\AppBundle\Entity\BaseEntityInterface;
use App\FiremanBundle\Entity\FiremanEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
#[ORM\Table(name: 'disponibilidade')]
#[ORM\UniqueConstraint(
    name: 'uniq_disponibilidade_bombeiro_data',
    columns: ['bombeiro_id', 'ano', 'mes', 'dia'],
)]
class AvailabilityEntity implements BaseEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'availabilities')]
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

    public function __construct(int $day, string $shift, ?int $month = null, ?int $year = null)
    {
        $now = new \DateTimeImmutable();
        $this->setDia($day);
        $this->setMes($month ?? (int) $now->format('n'));
        $this->setAno($year ?? (int) $now->format('Y'));
        $this->setTurno($shift);
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

    public function setDia(int $day): self
    {
        if ($day < 1 || $day > 31) {
            throw new \InvalidArgumentException('Dia deve estar entre 1 e 31');
        }

        $this->day = $day;

        return $this;
    }

    public function getMes(): int
    {
        return $this->month;
    }

    public function setMes(int $month): self
    {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException('Mês deve estar entre 1 e 12');
        }

        $this->month = $month;

        return $this;
    }

    public function getAno(): int
    {
        return $this->year;
    }

    public function setAno(int $year): self
    {
        if ($year < 2000 || $year > 2200) {
            throw new \InvalidArgumentException('Ano deve estar entre 2000 e 2200');
        }

        $this->year = $year;

        return $this;
    }

    public function getTurno(): string
    {
        return $this->shift;
    }

    public function setTurno(string $shift): self
    {
        if (!in_array($shift, CbmscConstants::getTurnosValidos(), true)) {
            throw new \InvalidArgumentException(
                'Turno deve ser um dos valores: '.implode(', ', CbmscConstants::getTurnosValidos()),
            );
        }

        $this->shift = $shift;

        return $this;
    }

    public function __toString(): string
    {
        return "Dia: {$this->day}, Mês: {$this->month}, Ano: {$this->year}, Turno: {$this->shift}";
    }

    public function equals(AvailabilityEntity $other): bool
    {
        return $this->day === $other->getDia()
            && $this->month === $other->getMes()
            && $this->year === $other->getAno()
            && $this->shift === $other->getTurno();
    }
}
