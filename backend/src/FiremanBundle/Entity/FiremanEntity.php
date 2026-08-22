<?php

namespace App\FiremanBundle\Entity;
use App\FiremanBundle\Repository\FiremanRepository;
use Doctrine\ORM\Mapping as ORM;

use AppBundle\Entity\BaseEntityInterface;

#[ORM\Table(name: 'bombeiro', schema: 'db_cbmsc')]
#[ORM\Entity(repositoryClass: FiremanRepository::class)]
class FiremanEntity implements \JsonSerializable, BaseEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\SequenceGenerator(sequenceName: 'db_cbmsc.id_bombeiro_seq', allocationSize: 1, initialValue: 1)]
    private int $id;

    #[ORM\Column(name: 'nome',type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'cpf', type: 'string', length: 14)]
    private string $cpf;

    #[ORM\Column(name: 'carteira_de_ambulancia', type: 'boolean')]
    private string $ambulanceLicense;

    #[ORM\Column(name: 'antiguidade', type: 'integer')]
    private string $seniority;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCpf(): ?string
    {
        return $this->cpf;
    }

    public function setCpf(string $cpf): self
    {
        $this->cpf = $cpf;

        return $this;
    }

    public function getAmbulanceLicense(): ?string
    {
        return $this->ambulanceLicense;
    }

    public function setAmbulanceLicense(string $ambulanceLicense): self
    {
        $this->ambulanceLicense = $ambulanceLicense;

        return $this;
    }

    public function getSeniority(): ?string
    {
        return $this->seniority;
    }

    public function setSeniority(string $seniority): self
    {
        $this->seniority = $seniority;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cpf' => $this->cpf,
            'ambulanceLicense' => $this->ambulanceLicense,
            'seniority' => $this->seniority,
        ];
    }
}