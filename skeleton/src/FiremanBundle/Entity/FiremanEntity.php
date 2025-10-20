<?php

namespace FiremanBundle\Entity;

use App\Constants\Cities;
use App\Constants\Seniority;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use UserBundle\Entity\CreatedAndUpdatedEntityTrait;

#[ORM\Table(name: 'bombeiro', schema: 'db_cbmsc')]
#[ORM\Entity(repositoryClass: \FiremanBundle\Repository\FiremanRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FiremanEntity implements JsonSerializable
{
    use CreatedAndUpdatedEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'nome', type: 'string', lenght: 100)]
    private string $name;

    #[ORM\Column(name: 'cpf', type: 'string', length: 11)] // VALIDAÇÃO
    private string $cpf;

    #[ORM\Column(name: 'carteira_de_ambulancia', type: 'boolean')]
    private bool $ambulanceLicense = false;

    #[ORM\Column(name: 'cidade_de_origem', type: 'string', length: 1)]
    private string $originCity = Cities::class;

    #[ORM\Column(name: '', type: '', length: 1)]
    private string $seniority = Seniority::class;

    // aqui vai a coluna de disponibilidades. Acredito que ela será uma entidade que se irá se relacionar com esta

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): FiremanEntity {
        $this->name = $name;

        return $this;
    }

    public function jsonSerialize(): array {

        return [

        ];
    }
}
    