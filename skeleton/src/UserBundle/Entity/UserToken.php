<?php

namespace UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UserBundle\Repository\UserTokenRepository;

#[ORM\Table(name: 'user_token')]
#[ORM\Entity(repositoryClass: UserTokenRepository::class)]
class UserToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $token;

    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'uid')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    #[ORM\Column(name: 'expired_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $expiredAt;

    public function __construct(User $user, string $token)
    {
        $this->expiredAt = new \DateTimeImmutable()->add(\DateInterval::createFromDateString('1 day'));
        $this->user = $user;
        $this->token = $token;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?\DateTimeImmutable $expiredAt): static
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }
}
