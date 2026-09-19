<?php

namespace App\FiremanBundle\Entity;

use App\AvailabilityBundle\Entity\AvailabilityEntity;
use App\AppBundle\Entity\BaseEntityInterface;
use App\FiremanBundle\Repository\FiremanRepository;
use App\ShiftBundle\Entity\ShiftEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FiremanRepository::class)]
#[ORM\Table(name: 'bombeiro')]
#[ORM\UniqueConstraint(name: 'uniq_bombeiro_cpf', columns: ['cpf'])]
class FiremanEntity implements BaseEntityInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private ?int $id = null;

    #[ORM\Column(name: 'nome', length: 150)]
    private string $name;

    #[ORM\Column(name: 'cpf', length: 11)]
    private string $cpf;

    #[ORM\Column(name: 'antiguidade', options: ['default' => 0])]
    private int $seniority = 0;

    #[ORM\Column(name: 'carteira_ambulancia', options: ['default' => false])]
    private bool $ambulanceLicense = false;

    #[ORM\Column(name: 'cidade_origem', length: 50, nullable: true)]
    private ?string $homeCity = null;

    /** @var Collection<int, AvailabilityEntity> */
    #[ORM\OneToMany(
        targetEntity: AvailabilityEntity::class,
        mappedBy: 'firefighter',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['year' => 'ASC', 'month' => 'ASC', 'day' => 'ASC'])]
    private Collection $availabilities;

    /** @var Collection<int, ShiftEntity> */
    #[ORM\OneToMany(
        targetEntity: ShiftEntity::class,
        mappedBy: 'firefighter',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['year' => 'ASC', 'month' => 'ASC', 'day' => 'ASC'])]
    private Collection $acquiredShifts;

    /** Pontuação temporária usada somente durante a distribuição. */
    private int $score = 0;

    public function __construct(string $name, string $cpf, bool $ambulanceLicense)
    {
        $this->availabilities = new ArrayCollection();
        $this->acquiredShifts = new ArrayCollection();
        $this->setNome($name);
        $this->setCpf($cpf);
        $this->setCarteiraAmbulancia($ambulanceLicense);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->name;
    }

    public function setNome(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCpf(): string
    {
        return $this->cpf;
    }

    public function setCpf(string $cpf): self
    {
        $normalizedCpf = preg_replace('/\D/', '', $cpf);
        $this->cpf = $normalizedCpf ?? $cpf;

        return $this;
    }

    public function getAntiguidade(): int
    {
        return $this->seniority;
    }

    public function setAntiguidade(int $seniority): self
    {
        $this->seniority = $seniority;

        return $this;
    }

    public function getCarteiraAmbulancia(): bool
    {
        return $this->ambulanceLicense;
    }

    public function setCarteiraAmbulancia(bool $ambulanceLicense): self
    {
        $this->ambulanceLicense = $ambulanceLicense;

        return $this;
    }

    public function getCidadeOrigem(): ?string
    {
        return $this->homeCity;
    }

    public function setCidadeOrigem(?string $homeCity): self
    {
        $this->homeCity = $homeCity;

        return $this;
    }

    public function getDisponibilidade(int $day): ?AvailabilityEntity
    {
        foreach ($this->availabilities as $availability) {
            if ($availability->getDia() === $day) {
                return $availability;
            }
        }

        return null;
    }

    /** @return list<AvailabilityEntity> */
    public function getDisponibilidades(): array
    {
        return array_values($this->availabilities->toArray());
    }

    /** @param list<AvailabilityEntity> $availabilities */
    public function setDisponibilidade(array $availabilities): self
    {
        foreach ($this->availabilities->toArray() as $availability) {
            $this->removerDisponibilidade($availability);
        }

        foreach ($availabilities as $availability) {
            $this->adicionarDisponibilidade($availability);
        }

        return $this;
    }

    public function adicionarDisponibilidade(AvailabilityEntity $availability): self
    {
        if (!$this->availabilities->contains($availability)) {
            $this->availabilities->add($availability);
            $availability->setBombeiro($this);
        }

        return $this;
    }

    public function removerDisponibilidade(AvailabilityEntity $availability): bool
    {
        foreach ($this->availabilities as $existing) {
            if ($existing === $availability || $existing->equals($availability)) {
                $this->availabilities->removeElement($existing);
                $existing->setBombeiro(null);

                return true;
            }
        }

        return false;
    }

    public function temDisponibilidade(int $day, string $shift): bool
    {
        foreach ($this->availabilities as $availability) {
            if ($availability->getDia() === $day && $availability->getTurno() === $shift) {
                return true;
            }
        }

        return false;
    }

    public function temDisponibilidadeParaDia(int $day): bool
    {
        return $this->getDisponibilidade($day) !== null;
    }

    public function adicionaTurnoAdquirido(ShiftEntity $shift): void
    {
        if (!$this->acquiredShifts->contains($shift)) {
            $this->acquiredShifts->add($shift);
            $shift->setBombeiro($this);
        }
    }

    public function removerTurnoAdquirido(ShiftEntity $shift): void
    {
        foreach ($this->acquiredShifts as $existing) {
            if ($existing->getDia() === $shift->getDia() && $existing->getTurno() === $shift->getTurno()) {
                $this->acquiredShifts->removeElement($existing);
                $existing->setBombeiro(null);
            }
        }
    }

    /** @return list<ShiftEntity> */
    public function getTurnosAdquiridos(): array
    {
        return array_values($this->acquiredShifts->toArray());
    }

    public function getDiasSolicitados(): int
    {
        if (trim($this->name) === 'BC CHEROBIN') {
            return PHP_INT_MAX;
        }

        return $this->availabilities->count();
    }

    public function setPontuacao(int $points): void
    {
        $this->score = $points;
    }

    public function getPontuacao(): int
    {
        return $this->score;
    }

    public function getPercentualDeServicosAceitos(): float
    {
        $requestedDays = $this->getDiasSolicitados();

        if ($requestedDays === 0) {
            return 0.0;
        }

        return round($this->acquiredShifts->count() * 100 / $requestedDays, 2);
    }

    public function exibirDados(): string
    {
        return "Nome: {$this->name}, CPF: {$this->cpf}, Antiguidade: {$this->seniority}, Carteira Ambulância: {$this->ambulanceLicense}";
    }

    public function print_disponibilidade(): void
    {
        foreach ($this->availabilities as $availability) {
            echo $availability.'<br>';
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->name,
            'cpf' => $this->cpf,
            'antiguidade' => $this->seniority,
            'carteiraAmbulancia' => $this->ambulanceLicense,
            'cidadeOrigem' => $this->homeCity,
        ];
    }
}
