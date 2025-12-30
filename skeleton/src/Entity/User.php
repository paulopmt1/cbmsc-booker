<?php

namespace App\Entity;

use App\Repository\UserRepository;
use AppBundle\Entity\BaseEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users', schema: 'public')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, BaseEntityInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'user_name', type: 'string', length: 60, unique: true, options: ['default' => ''])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 60)]
    private string $userName;

    #[ORM\Column(name: 'first_name', type: 'string', length: 60)]
    #[Assert\NotBlank]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: 'string', length: 60)]
    #[Assert\NotBlank]
    private string $lastName;

    #[ORM\Column(name: 'email', type: 'string', length: 180, unique: true, options: ['default' => ''])]
    #[Assert\Email]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(name: 'roles', type: 'json', length: 255)]
    private array $roles = [];

    #[ORM\Column(name: 'password', type: 'string', length: 60)]
    private ?string $password = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function setUserName($userName): User
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'userName' => $this->userName,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'roles' => $this->roles,
        ];
    }
}
