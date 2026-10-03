<?php

namespace App\AvailabilityBundle\Repository;

use App\AvailabilityBundle\Entity\AvailabilityEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AvailabilityEntity> */
class AvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvailabilityEntity::class);
    }
}
