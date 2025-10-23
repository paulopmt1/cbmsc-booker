<?php

namespace UserBundle\Entity;

use AppBundle\Dto\UserDataDto;
use Doctrine\ORM\Mapping as ORM;

trait UpdatedByUserEntityTrait
{
    /**
     * @var User|null
     */
    #[ORM\JoinColumn(name: 'atualizado_pelo_usuario', referencedColumnName: 'uid', nullable: false)]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $updatedByUser;

    public function getUpdatedByUser(): ?User
    {
        return $this->updatedByUser;
    }

    public function setUpdatedByUser(User|UserDataDto $updatedBy): self
    {
        if ($updatedBy instanceof UserDataDto) {
            $this->updatedByUser = $updatedBy->getUser();

            return $this;
        }

        $this->updatedByUser = $updatedBy;

        return $this;
    }

    /**
     * @return array{id: int, fullName: string, firstName: string, lastName: string}|null
     */
    public function getUpdatedByUserJson(): ?array
    {
        return null === $this->updatedByUser ? null : [
            'id' => $this->updatedByUser->getUid(),
            'fullName' => $this->updatedByUser->getFullName(),
            'firstName' => $this->updatedByUser->getFirstName(),
            'lastName' => $this->updatedByUser->getLastName(),
        ];
    }
}
