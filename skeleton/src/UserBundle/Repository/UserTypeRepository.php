<?php

namespace UserBundle\Repository;

use AppBundle\Repository\RepositoryService;
use Doctrine\Persistence\ManagerRegistry;
use UserBundle\Entity\UserType;

/**
 * @extends RepositoryService<UserType>
 */
class UserTypeRepository extends RepositoryService
{
    protected string $notFoundMessage = 'Tipo de usuário não encontrado.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserType::class);
    }
}
