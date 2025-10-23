<?php

namespace UserBundle\Entity;

use AppBundle\Entity\BaseEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'programas', schema: 'db_sistema')]
#[ORM\UniqueConstraint(columns: ['pai_id', 'nome'])]
#[ORM\Entity(repositoryClass: \UserBundle\Repository\ProgramRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Program implements \JsonSerializable, BaseEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'nome', type: 'string', length: 50)]
    private string $name;

    #[ORM\Column(name: 'visivel', type: 'boolean', options: ['default' => true])]
    private bool $visible = true;

    #[ORM\Column(name: 'somente_relatorio', type: 'boolean', options: ['default' => false])]
    private bool $reportOnly = false;

    #[ORM\JoinColumn(name: 'pai_id', referencedColumnName: 'id', nullable: true)]
    #[ORM\ManyToOne(targetEntity: Program::class, inversedBy: 'children')]
    private Program $parent;

    /**
     * @var ArrayCollection|Collection<int, Program>
     */
    #[ORM\OneToMany(targetEntity: Program::class, mappedBy: 'parent')]
    private Collection|ArrayCollection $children;

    #[ORM\Column(name: 'criado_em', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'atualizado_em', type: 'datetime_immutable', nullable: true)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable('now');
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatedTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return (bool|int|string)[]
     *
     * @psalm-return array{id: int, name: string, visible: bool}
     */
    public function minimalJsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'visible' => $this->visible,
        ];
    }

    public function setId(int $id): Program
    {
        $this->id = $id;

        return $this;
    }

    public function setName(string $name): Program
    {
        $this->name = $name;

        return $this;
    }

    public function setVisible(bool $visible): Program
    {
        $this->visible = $visible;

        return $this;
    }

    public function setParent(Program $parent): Program
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @param ArrayCollection<int, Program> $children
     *
     * @return $this
     */
    public function setChildren(ArrayCollection $children): Program
    {
        $this->children = $children;

        return $this;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): Program
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): Program
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function getParent(): Program
    {
        return $this->parent;
    }

    /**
     * @return ArrayCollection<int, Program>|Collection<int, Program>
     */
    public function getChildren(): ArrayCollection|Collection
    {
        return $this->children;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isReportOnly(): bool
    {
        return $this->reportOnly;
    }

    public function setReportOnly(bool $reportOnly): Program
    {
        $this->reportOnly = $reportOnly;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'visible' => $this->visible,
            'children' => $this->children->toArray(),
        ];
    }
}
