<?php

namespace UserBundle\Entity;

use AppBundle\Entity\Feature;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'perfil_usuario_permissao', schema: 'db_sistema')]
#[ORM\UniqueConstraint(name: 'unique_perfil_usuario_permissao', columns: ['perfil_usuario_permissao_functionality_id', 'perfil_usuario_permissao_profile_id'])]
#[ORM\Entity]
class UserProfileRoles implements \JsonSerializable
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'perfil_usuario_permissao_id', type: 'integer')]
    private $id;

    /**
     * @var Feature
     */
    #[ORM\JoinColumn(name: 'perfil_usuario_permissao_functionality_id', referencedColumnName: 'func_id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: Feature::class, inversedBy: 'roles')]
    private $feature;

    /**
     * @var int
     */
    #[ORM\Column(name: 'perfil_usuario_permissao_action', type: 'integer', length: 80)]
    #[Assert\NotBlank]
    private $action;

    /**
     * @var UserProfile
     */
    #[ORM\JoinColumn(name: 'perfil_usuario_permissao_profile_id', referencedColumnName: 'perfil_usuario_id', nullable: false)]
    #[ORM\ManyToOne(targetEntity: UserProfile::class, inversedBy: 'roles')]
    private $profile;

    public function __construct(UserProfile $profile, Feature $feature, int $action)
    {
        $this->profile = $profile;
        $this->feature = $feature;
        $this->action = $action;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): UserProfileRoles
    {
        $this->id = $id;

        return $this;
    }

    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function setFeature(Feature $feature): UserProfileRoles
    {
        $this->feature = $feature;

        return $this;
    }

    public function getAction(): int
    {
        return $this->action;
    }

    public function setAction(int $action): UserProfileRoles
    {
        $this->action = $action;

        return $this;
    }

    public function getProfile(): UserProfile
    {
        return $this->profile;
    }

    public function setProfile(UserProfile $profile): UserProfileRoles
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * @return array{id: int, feature: Feature, action: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'feature' => $this->feature,
            'action' => $this->action,
        ];
    }
}
