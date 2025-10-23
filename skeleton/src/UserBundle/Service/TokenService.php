<?php

namespace UserBundle\Service;

use AppBundle\Exception\NotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UserBundle\Entity\User;
use UserBundle\Entity\UserToken;
use UserBundle\Repository\UserRepository;
use UserBundle\Repository\UserTokenRepository;

class TokenService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserTokenRepository $tokenRepository,
    ) {
    }

    /**
     * @throws OptimisticLockException
     * @throws NonUniqueResultException
     */
    public function generateTokenByEmail(string $email): string
    {
        $token = $this->getToken();
        $user = $this->userRepository->findByEmailOrCreate($email);

        if (null === $user) {
            throw new NotFoundHttpException('Usuário não encontrado.');
        }

        $userToken = $this->tokenRepository->save(new UserToken($user, $token));

        return $userToken->getToken();
    }

    /**
     * @throws OptimisticLockException
     * @throws NonUniqueResultException
     */
    public function generateTokenByUsername(string $username): string
    {
        $token = $this->getToken();
        $user = $this->userRepository->findByUsernameOrCreate($username);

        if (null === $user) {
            throw new NotFoundHttpException('Usuário não encontrado.');
        }

        $userToken = $this->tokenRepository->save(new UserToken($user, $token));

        return $userToken->getToken();
    }

    /**
     * @throws OptimisticLockException
     * @throws NotFoundException
     */
    public function generateApiTokenById(int $id): string
    {
        /** @var User $user */
        $user = $this->userRepository->findByIdOrNotFound($id);
        $token = new UserToken($user, $this->getToken());
        $token->setExpiredAt(null);
        $userToken = $this->tokenRepository->save($token);

        return $userToken->getToken();
    }

    /**
     * @throws \DateMalformedStringException
     * @throws OptimisticLockException
     * @throws NonUniqueResultException
     */
    public function saveTokenGoogle(
        string $token,
        string $expireIn,
        string $email,
        string $firstName,
        string $lastName,
        string $picture,
    ): UserToken {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new NotFoundHttpException('O e-mail em questão não está autorizado a acessar este sistema.');
        }

        $user->setFirstName($firstName);

        if ('' !== $lastName) {
            $user->setLastName($lastName);
        }

        $user->setPicture($picture);
        $this->userRepository->save($user);

        return $this->tokenRepository->save((new UserToken($user, $token))->setExpiredAt(new \DateTimeImmutable($expireIn)));
    }

    private function getToken(): string
    {
        return \sha1(\uniqid((string) \mt_rand(), true).'-'.new \DateTime()->getTimestamp());
    }
}
