<?php

namespace UserBundle\Entity;

use AppBundle\Dto\UserDataDto;
use Doctrine\ORM\Mapping as ORM;

trait CreatedByUserEntityTrait
{
    #[ORM\JoinColumn(name: 'criado_pelo_usuario_id', referencedColumnName: 'uid', nullable: false)]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $createdBy;

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User|UserDataDto $createdBy): static
    {
        if ($createdBy instanceof UserDataDto) {
            $this->createdBy = $createdBy->getUser();

            return $this;
        }

        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return ?array{id: int, fullName: string, firstName: string, lastName: string}
     */
    public function getCreatedByUserJson(): ?array
    {
        return [
            'id' => $this->createdBy->getUid(),
            'fullName' => $this->createdBy->getFullName(),
            'firstName' => $this->createdBy->getFirstName(),
            'lastName' => $this->createdBy->getLastName(),
        ];
    }
}
