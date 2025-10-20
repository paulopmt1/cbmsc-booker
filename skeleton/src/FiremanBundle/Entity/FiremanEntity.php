<?php

namespace FiremanBundle\Entity;

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

    #[ORM\Column(name: 'antiguidade', type: 'string', length: '')]

    public function jsonSerialize(): array {

        return [

        ];
    }
}
    