<?php

namespace UserBundle\Entity;

use AppBundle\Entity\BaseEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use UserBundle\Repository\UserTypeRepository;

#[ORM\Table(name: 'user_types', schema: 'public')]
#[ORM\Entity(repositoryClass: UserTypeRepository::class)]
class UserType implements \JsonSerializable, BaseEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $name;

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array{id: ?int, name: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
