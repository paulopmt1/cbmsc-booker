<?php

namespace App\AvailabilityBundle\Repository;

use App\AvailabilityBundle\Entity\AvailabilityEntity;
use AppBundle\Repository\RepositoryService;
use Doctrine\Persistence\ManagerRegistry;

class AvailabilityRepository extends RepositoryService
{
    protected string $notFoundMessage = '';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvailabilityEntity::class);
    }
}