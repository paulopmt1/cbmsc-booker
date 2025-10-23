<?php

namespace UserBundle\Repository;

use AppBundle\Repository\Repository;
use Doctrine\ORM\NonUniqueResultException;
use UserBundle\Entity\User;

/**
 * @extends Repository<User>
 */
class UserRepository extends Repository
{
    protected string $notFoundMessage = 'Usuário não encontrado';

    /**
     * @throws NonUniqueResultException
     */
    public function findByEmail(string $email): ?User
    {
        /** @var ?User $user */
        $user = $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        return $user;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByUsername(string $username): ?User
    {
        /** @var ?User $user */
        $user = $this->createQueryBuilder('u')
            ->where('u.email = :username')
            ->orWhere('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();

        return $user;
    }

    /**
     * @return User
     */
    public function createWithEmail(string $email)
    {
        return User::createWithEmail($email);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByEmailOrCreate(string $email): ?User
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            $user = User::createWithEmail($email);

            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        }

        return $this->findByEmail($email);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByUsernameOrCreate(string $username): ?User
    {
        $user = $this->findByUsername($username);

        if (!$user) {
            $user = User::createWithEmail($username);

            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        }

        return $this->findByUsername($username);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function save(User $user, bool $flush = true): ?User
    {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this->findByEmail($user->getEmail());
    }

    public function remove(User $user, bool $flush = true): void
    {
        $this->getEntityManager()->remove($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws NonUniqueResultException
     */
    public function existsByEmail(string $email, ?int $id = 0): ?User
    {
        /** @var ?User $user */
        $user = $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->andWhere('u.uid != :id')
            ->setParameter('email', $email)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $user;
    }
}
