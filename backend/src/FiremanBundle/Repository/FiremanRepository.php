<?php

namespace App\FiremanBundle\Repository;

use App\FiremanBundle\Entity\FiremanEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<FiremanEntity> */
class FiremanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FiremanEntity::class);
    }

    public function findOneByCpf(string $cpf): ?FiremanEntity
    {
        return $this->findOneBy([
            'cpf' => preg_replace('/\D/', '', $cpf) ?? $cpf,
        ]);
    }
}
