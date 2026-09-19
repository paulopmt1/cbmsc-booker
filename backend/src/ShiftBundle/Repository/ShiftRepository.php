<?php

namespace App\ShiftBundle\Repository;

use App\ShiftBundle\Entity\ShiftEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ShiftEntity> */
class ShiftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShiftEntity::class);
    }

    /** @return list<ShiftEntity> */
    public function findDoPeriodo(int $month, int $year): array
    {
        return $this->findBy(
            ['month' => $month, 'year' => $year],
            ['day' => 'ASC'],
        );
    }
}
