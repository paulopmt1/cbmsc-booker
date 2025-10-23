<?php

namespace UserBundle\Entity;

use AppBundle\Entity\BaseEntityInterface;
use AppBundle\Service\FeatureService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use LibrariesBundle\Entity\ResponsibleEntity;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UserBundle\Repository\UserRepository;

#[ORM\Table(name: 'users', schema: 'public')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, \JsonSerializable, BaseEntityInterface
{
    public static function createWithEmail(string $email): User
    {
        return (new self())
            ->setUsername(\explode('@', $email)[0])
            ->setFirstName('')
            ->setLastName('')
            ->setPassword('')
            ->setMode(0)
            ->setSort(0)
            ->setThreshold(0)
            ->setTheme('')
            ->setSignature('')
            ->setSignatureFormat(0)
            ->setCreated(\time())
            ->setAccess(0)
            ->setLogin(0)
            ->setStatus(0)
            ->setTimezone(null)
            ->setLanguage('pt')
            ->setPicture('')
            ->setInit(null)
            ->setData(null)
            ->setEmail($email);
    }

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'uid', type: 'integer')]
    private $uid;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 60, unique: true, options: ['default' => ''])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 60)]
    private $username;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'first_name', type: 'string')]
    #[Assert\NotBlank]
    private $firstName;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'last_name', type: 'string')]
    #[Assert\NotBlank]
    private $lastName;

    /**
     * @var string
     */
    #[ORM\Column(name: 'pass', type: 'string', length: 32, options: ['default' => ''])]
    private $password;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mail', type: 'string', length: 64, options: ['default' => ''])]
    #[Assert\NotBlank]
    private $email;

    /**
     * @var int
     */
    #[ORM\Column(name: 'mode', type: 'integer', options: ['default' => 0])]
    private $mode;

    /**
     * @var int
     */
    #[ORM\Column(name: 'sort', type: 'integer', options: ['default' => 0])]
    private $sort;

    /**
     * @var int
     */
    #[ORM\Column(name: 'threshold', type: 'integer', options: ['default' => 0])]
    private $threshold;

    /**
     * @var string
     */
    #[ORM\Column(name: 'theme', type: 'string', options: ['default' => ''])]
    private $theme;

    /**
     * @var string
     */
    #[ORM\Column(name: 'signature', type: 'string', options: ['default' => ''])]
    private $signature;

    /**
     * @var int
     */
    #[ORM\Column(name: 'signature_format', type: 'integer', options: ['default' => 0])]
    private $signature_format;

    /**
     * @var int
     */
    #[ORM\Column(name: 'created', type: 'integer', options: ['default' => 0])]
    private $created;

    /**
     * @var int
     */
    #[ORM\Column(name: 'access', type: 'integer', options: ['default' => 0])]
    private $access;

    /**
     * @var int
     */
    #[ORM\Column(name: 'login', type: 'integer', options: ['default' => 0])]
    private $login;

    /**
     * @var int
     */
    #[ORM\Column(name: 'status', type: 'integer', options: ['default' => 0])]
    private $status;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'timezone', type: 'string', length: 8)]
    private $timezone;

    /**
     * @var string
     */
    #[ORM\Column(name: 'language', type: 'string', length: 12, options: ['default' => ''])]
    #[Assert\NotBlank]
    private $language;

    /**
     * @var string
     */
    #[ORM\Column(name: 'picture', type: 'string', options: ['default' => ''])]
    private $picture;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'init', type: 'string', length: 64, options: ['default' => ''])]
    private $init;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'data', type: 'string')]
    private $data;

    /**
     * @var UserProfile|null
     */
    #[ORM\JoinColumn(name: 'usuario_profile_id', referencedColumnName: 'perfil_usuario_id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: UserProfile::class, inversedBy: 'users')]
    private $profile;

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'is_admin', type: 'boolean')]
    private $is_admin;

    /** @var Collection<int, UserType> */
    #[ORM\JoinTable(name: 'user_user_types')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'uid')]
    #[ORM\InverseJoinColumn(name: 'user_type_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: UserType::class)]
    private Collection $userTypes;

    /**
     * @var Program|null
     */
    #[ORM\JoinColumn(name: 'programa_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: Program::class)]
    private $program;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: ResponsibleEntity::class, cascade: ['persist'])]
    private ResponsibleEntity $responsible;

    public function __construct()
    {
        $this->userTypes = new ArrayCollection();
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getId(): ?int
    {
        return $this->uid;
    }

    public function getRoles()
    {
        $roles = [];

        if (!\is_null($this->profile)) {
            foreach ($this->profile->getRoles() as $role) {
                $alias = $role->getFeature()->getAlias();
                if (null !== $alias) {
                    $roles = \array_merge($roles, FeatureService::getRoles($alias, $role->getAction()));
                }
            }
        }

        return $roles;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password): User
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt()
    {
        return null;
    }

    /**
     * @return void
     */
    public function eraseCredentials()
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username): User
    {
        $this->username = $username;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName ?? '';
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName): User
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName ?? '';
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName): User
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email): User
    {
        $this->email = $email;

        return $this;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode($mode): User
    {
        $this->mode = $mode;

        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     */
    public function setSort($sort): User
    {
        $this->sort = $sort;

        return $this;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    /**
     * @param int $threshold
     */
    public function setThreshold($threshold): User
    {
        $this->threshold = $threshold;

        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * @param string $theme
     */
    public function setTheme($theme): User
    {
        $this->theme = $theme;

        return $this;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     */
    public function setSignature($signature): User
    {
        $this->signature = $signature;

        return $this;
    }

    public function getSignatureFormat(): int
    {
        return $this->signature_format;
    }

    /**
     * @param int $signature_format
     */
    public function setSignatureFormat($signature_format): User
    {
        $this->signature_format = $signature_format;

        return $this;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    /**
     * @param int $created
     */
    public function setCreated($created): User
    {
        $this->created = $created;

        return $this;
    }

    public function getAccess(): int
    {
        return $this->access;
    }

    /**
     * @param int $access
     */
    public function setAccess($access): User
    {
        $this->access = $access;

        return $this;
    }

    public function getLogin(): int
    {
        return $this->login;
    }

    /**
     * @param int $login
     */
    public function setLogin($login): User
    {
        $this->login = $login;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status): User
    {
        $this->status = $status;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * @param string|null $timezone
     */
    public function setTimezone($timezone): User
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language): User
    {
        $this->language = $language;

        return $this;
    }

    public function getPicture(): string
    {
        return $this->picture;
    }

    /**
     * @param string $picture
     */
    public function setPicture($picture): User
    {
        $this->picture = $picture;

        return $this;
    }

    public function getInit(): ?string
    {
        return $this->init;
    }

    /**
     * @param string|null $init
     */
    public function setInit($init): User
    {
        $this->init = $init;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    /**
     * @param string|null $data
     */
    public function setData($data): User
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return ?UserProfile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    public function setProfile(UserProfile $profile): User
    {
        $this->profile = $profile;

        return $this;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * @param bool $isAdmin
     */
    public function setIsAdmin($isAdmin): User
    {
        $this->is_admin = $isAdmin;

        return $this;
    }

    /**
     * @return Collection<int, UserType>
     */
    public function getUserTypes(): Collection
    {
        return $this->userTypes;
    }

    public function addUserType(UserType $type): static
    {
        if (!$this->userTypes->contains($type)) {
            $this->userTypes->add($type);
        }

        return $this;
    }

    public function removeUserType(UserType $type): User
    {
        $this->userTypes->removeElement($type);

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName.' '.$this->lastName;
    }

    /**
     * @return $this
     */
    public function setProgram(?Program $program): User
    {
        if ($program?->isReportOnly()) {
            throw new \InvalidArgumentException('Não é possível associar o programa ao usuário');
        }

        $this->program = $program;

        return $this;
    }

    /**
     * @return Program|null
     */
    public function getProgram()
    {
        return $this->program;
    }

    /**
     * @throws BadRequestHttpException
     *
     * @return Program
     */
    public function getProgramOrThrowException()
    {
        if (!$this->program) {
            throw new BadRequestHttpException('O usuário não possui nenhum programa vinculado.');
        }

        return $this->program;
    }

    public function getResponsible(): ResponsibleEntity
    {
        return $this->responsible;
    }

    public function setResponsible(ResponsibleEntity $responsible): self
    {
        $this->responsible = $responsible;

        return $this;
    }

    public function setUid(int $uid): User
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @param Collection<int, UserType> $userTypes
     *
     * @return $this
     */
    public function setUserTypes(Collection $userTypes): User
    {
        $this->userTypes = $userTypes;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'uid' => $this->uid,
            'username' => $this->username,
            'full_name' => $this->getFullName(),
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'language' => $this->language,
            'theme' => $this->theme,
            'picture' => $this->picture,
            'initials' => \strtoupper(\substr($this->firstName ?? '', 0, 1)).\strtoupper(\substr($this->lastName ?? '', 0, 1)),
            'profile' => $this->profile,
            'is_admin' => $this->is_admin,
            'user_types' => $this->userTypes,
            'program' => $this->program ? $this->program->minimalJsonSerialize() : null,
        ];
    }
}
