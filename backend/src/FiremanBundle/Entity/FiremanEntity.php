<?php

namespace App\FiremanBundle\Entity;

use App\Entity\BaseEntityInterface;
use App\FiremanBundle\Repository\FiremanRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'bombeiro')]
#[ORM\UniqueConstraint(name: 'uniq_bombeiro_cpf', columns: ['cpf'])]
#[ORM\Entity(repositoryClass: FiremanRepository::class)]
class FiremanEntity implements BaseEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'nome', type: 'string', length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private string $name = '';

    #[ORM\Column(name: 'cpf', type: 'string', length: 11)]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 11)]
    private string $cpf = '';

    #[ORM\Column(name: 'antiguidade', type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero]
    private int $seniority = 0;

    #[ORM\Column(name: 'carteira_ambulancia', type: 'boolean', options: ['default' => false])]
    private bool $ambulanceLicense = false;

    #[ORM\Column(name: 'cidade_origem', type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $originCity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
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
        $this->cpf = $cpf;

        return $this;
    }

    public function getSeniority(): int
    {
        return $this->seniority;
    }

    public function setSeniority(int $seniority): self
    {
        $this->seniority = $seniority;

        return $this;
    }

    public function hasAmbulanceLicense(): bool
    {
        return $this->ambulanceLicense;
    }

    public function setAmbulanceLicense(bool $ambulanceLicense): self
    {
        $this->ambulanceLicense = $ambulanceLicense;

        return $this;
    }

    public function getOriginCity(): ?string
    {
        return $this->originCity;
    }

    public function setOriginCity(?string $originCity): self
    {
        $this->originCity = $originCity;

        return $this;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cpf' => $this->cpf,
            'seniority' => $this->seniority,
            'ambulanceLicense' => $this->ambulanceLicense,
            'originCity' => $this->originCity,
        ];
    }
}
