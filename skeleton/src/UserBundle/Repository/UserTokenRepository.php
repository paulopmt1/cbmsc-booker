<?php

namespace UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use UserBundle\Entity\User;
use UserBundle\Entity\UserToken;

class UserTokenRepository extends EntityRepository
{
    /**
     * @param UserToken $userToken
     * @param bool $flush
     *
     * @throws OptimisticLockException
     *
     * @return UserToken
     */
    public function save($userToken, $flush = true)
    {
        $this->getEntityManager()->persist($userToken);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $userToken;
    }

    /**
     * @param string $token
     *
     * @throws NonUniqueResultException
     *
     * @return UserToken|null
     */
    public function findByToken($token)
    {
        return $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     *
     * @return array<UserToken>
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
