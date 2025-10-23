<?php

namespace UserBundle\Entity;

use AppBundle\Entity\BaseEntityInterface;
use AppBundle\Entity\Feature;
use AppBundle\Exception\UniqueException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ReportBundle\Entity\ReportCategoryEntity;
use ReportBundle\Entity\ReportEntity;
use UserBundle\Repository\UserProfileRepository;

#[ORM\Table(name: 'perfil_usuario', schema: 'db_sistema')]
#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
class UserProfile implements \JsonSerializable, BaseEntityInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'perfil_usuario_id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'perfil_usuario_nome', type: 'string', length: 80, unique: true, options: ['default' => ''])]
    private string $name;

    /**
     * @var Collection<int, UserProfileRoles>
     */
    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: UserProfileRoles::class, cascade: ['persist', 'remove'])]
    private Collection $roles;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: User::class)]
    private Collection $users;

    /**
     * @var Collection<int, ReportCategoryEntity>
     */
    #[ORM\JoinTable(name: 'perfil_usuario_permissao_categoria_relatorio', schema: 'db_sistema')]
    #[ORM\JoinColumn(name: 'perfil_usuario_id', referencedColumnName: 'perfil_usuario_id')]
    #[ORM\InverseJoinColumn(name: 'relatorio_categoria_id', referencedColumnName: 'relatorio_categoria_id')]
    #[ORM\ManyToMany(targetEntity: ReportCategoryEntity::class)]
    private Collection $allowedReportingCategories;

    /**
     * @var Collection<int, ReportEntity>
     */
    #[ORM\JoinTable(name: 'perfil_usuario_permissao_relatorio', schema: 'db_sistema')]
    #[ORM\JoinColumn(name: 'perfil_usuario_id', referencedColumnName: 'perfil_usuario_id')]
    #[ORM\InverseJoinColumn(name: 'relatorio_id', referencedColumnName: 'relatorio_id')]
    #[ORM\ManyToMany(targetEntity: ReportEntity::class)]
    private Collection $allowedReports;

    #[ORM\Column(name: 'filtrar_financiadores_sociobio', type: 'boolean', options: ['default' => false])]
    private bool $filterSociobioSponsors = false;

    #[ORM\Column(name: 'filtrar_financiadores_programa', type: 'boolean', options: ['default' => false])]
    private bool $filterProgramSponsors = false;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->allowedReportingCategories = new ArrayCollection();
        $this->allowedReports = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): UserProfile
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): UserProfile
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, UserProfileRoles>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * @throws UniqueException
     */
    public function addRole(UserProfileRoles $role): UserProfile
    {
        foreach ($this->roles as $currentRole) {
            if ($role->getFeature() === $currentRole->getFeature()) {
                throw new UniqueException('Não é possível cadastrar a mesma permissão mais de uma vez no mesmo perfil de usuário');
            }
        }

        $this->roles[] = $role;

        return $this;
    }

    public function removeRole(UserProfileRoles $role): static
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
        }

        return $this;
    }

    public function getRoleByFeature(Feature $feature): ?UserProfileRoles
    {
        return $this->roles->findFirst(fn ($key, UserProfileRoles $item) => $feature === $item->getFeature());
    }

    public function hasUser(): bool
    {
        return $this->users->count() > 0;
    }

    public function hasUserId(int $id): bool
    {
        return (bool) $this->users->filter(function (User $user) use ($id) {
            return $user->getUid() === $id;
        })->first();
    }

    public function hasReportingPermissions(): bool
    {
        return $this->allowedReportingCategories->count() > 0 || $this->allowedReports->count() > 0;
    }

    /**
     * @return array<int>
     */
    public function getAllowedReportingCategoryIds(): array
    {
        return \array_map(
            fn (ReportCategoryEntity $c) => $c->getId(),
            $this->allowedReportingCategories->toArray()
        );
    }

    /**
     * @return array<int>
     */
    public function getAllowedReportIds(): array
    {
        return \array_map(
            fn (ReportEntity $r) => $r->getId(),
            $this->allowedReports->toArray()
        );
    }

    public function hasReportingCategory(int $categoryId): bool
    {
        return \in_array($categoryId, $this->getAllowedReportingCategoryIds());
    }

    public function hasAllowedReport(int $reportId, int $categoryId): bool
    {
        return \in_array($reportId, $this->getAllowedReportIds()) || $this->hasReportingCategory($categoryId);
    }

    /**
     * @param ReportCategoryEntity[] $categories
     * @param ReportEntity[] $reports
     */
    public function updateReportPermissions(array $categories, array $reports): void
    {
        $newCategoryIds = \array_map(fn (ReportCategoryEntity $c) => $c->getId(), $categories);
        foreach ($this->allowedReportingCategories as $existingCategory) {
            if (!\in_array($existingCategory->getId(), $newCategoryIds)) {
                $this->allowedReportingCategories->removeElement($existingCategory);
            }
        }

        foreach ($categories as $category) {
            if (!$this->allowedReportingCategories->contains($category)) {
                $this->allowedReportingCategories->add($category);
            }
        }

        $newReportIds = \array_map(fn (ReportEntity $r) => $r->getId(), $reports);
        foreach ($this->allowedReports as $existingReport) {
            $reportCategoryId = $existingReport->getCategory()->getId();

            if (!\in_array($existingReport->getId(), $newReportIds) || \in_array($reportCategoryId, $newCategoryIds)) {
                $this->allowedReports->removeElement($existingReport);
            }
        }

        foreach ($reports as $report) {
            $reportCategoryId = $report->getCategory()->getId();

            if (!\in_array($reportCategoryId, $newCategoryIds) && !$this->allowedReports->contains($report)) {
                $this->allowedReports->add($report);
            }
        }
    }

    public function addAllowedReportingCategory(ReportCategoryEntity $category): void
    {
        if (!$this->allowedReportingCategories->contains($category)) {
            $this->allowedReportingCategories->add($category);
        }
    }

    public function shouldFilterSociobioSponsors(): bool
    {
        return $this->filterSociobioSponsors;
    }

    public function setFilterSociobioSponsors(bool $filter): self
    {
        $this->filterSociobioSponsors = $filter;

        return $this;
    }

    public function shouldFilterProgramSponsors(): bool
    {
        return $this->filterProgramSponsors;
    }

    public function setFilterProgramSponsors(bool $filter): self
    {
        $this->filterProgramSponsors = $filter;

        return $this;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     roles: Collection<int, UserProfileRoles>,
     *     allowedReportingCategoryIds: int[],
     *     allowedReportIds: int[]
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'roles' => $this->roles,
            'filterSociobioSponsors' => $this->filterSociobioSponsors,
            'filterProgramSponsors' => $this->filterProgramSponsors,
            'allowedReportingCategoryIds' => $this->getAllowedReportingCategoryIds(),
            'allowedReportIds' => $this->getAllowedReportIds(),
        ];
    }
}
