<?php

namespace App\FiremanBundle\Entity;

use App\AvailabilityBundle\Entity\AvailabilityEntity;
use App\Constants\Seniority;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'bombeiro', schema: 'db_cbmsc')]
#[ORM\Entity(repositoryClass: \FiremanBundle\Repository\FiremanRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FiremanEntity implements UserInterface, JsonSerializable
{

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(name: 'nome', type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(name: 'cpf', type: 'string', length: 11)] // VALIDAÇÃO
    private string $cpf;

    #[ORM\Column(name: 'carteira_de_ambulancia', type: 'boolean')]
    private bool $ambulanceLicense = false;

    #[ORM\Column(name: 'cidade_de_origem', type: 'string', length: 1)]
    #[Assert\Choice(choices: ['Fraiburgo', 'Videira', 'Caçador'])]
    private string $originCity;

    #[ORM\Column(name: 'antiguidade', type: 'string', length: 1)]
    private string $seniority = Seniority::class;

    #[ORM\Column(name: 'mail', type: 'string', length: 64, options: ['default' => ''])]
    #[Assert\NotBlank]
    private string $email;

    #[ORM\Column(name: 'pass', type: 'string', length: 32, options: ['default' => ''])]
    private string $password;

    #[ORM\JoinTable(name: '')]
    #[ORM\JoinColumn(name: '', referencedColumnName: '')]
    #[ORM\InverseJoinColumn(name: '', referencedColumnName: '')]
    #[ORM\ManyToMany(targetEntity: AvailabilityEntity::class)]
    private ArrayCollection $availability;

    public function __construct()
    {
        $this->availability = new ArrayCollection();
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function setUid(int $uid): FiremanEntity
    {
        $this->uid = $uid;

        return $this;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): FiremanEntity {
        $this->name = $name;

        return $this;
    }

    public function getRoles(): array
    {
        // TODO: Implement getRoles() method.
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        // TODO: Implement getUserIdentifier() method.
    }

    /*
     *  @return Collection<int, Availability>
     */
    public function getAvailability(): Collection {}

    public function jsonSerialize(): array {

        return [
            'id' => $this->uid,
            'name' => $this->name,
            'cpf' => $this->cpf,
            'originCity' => $this->originCity,
            'seniority' => $this->seniority,
            'availability' => $this->seniority->toArray(),
        ];
    }
}
    