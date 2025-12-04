<?php

namespace App\Entity;

use App\Repository\PermissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PermissionRepository::class)]
class Permission
{
    // Modules disponibles
    public const MODULE_COURSES = 'courses';
    public const MODULE_CHAUFFEURS = 'chauffeurs';
    public const MODULE_MESSAGES = 'messages';
    public const MODULE_TRANSACTIONS = 'transactions';
    public const MODULE_DOCUMENTS = 'documents';
    public const MODULE_SETTINGS = 'settings';
    public const MODULE_REPORTS = 'reports';

    // Actions disponibles
    public const ACTION_READ = 'read';
    public const ACTION_WRITE = 'write';
    public const ACTION_DELETE = 'delete';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $module = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json')]
    private array $actions = [];

    #[ORM\ManyToMany(targetEntity: Role::class, mappedBy: 'permissions')]
    private Collection $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModule(): ?string
    {
        return $this->module;
    }

    public function setModule(string $module): static
    {
        $this->module = $module;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function setActions(array $actions): static
    {
        $this->actions = $actions;
        return $this;
    }

    public function hasAction(string $action): bool
    {
        return in_array($action, $this->actions, true);
    }

    public function addAction(string $action): static
    {
        if (!in_array($action, $this->actions, true)) {
            $this->actions[] = $action;
        }
        return $this;
    }

    public function removeAction(string $action): static
    {
        $this->actions = array_filter($this->actions, fn($a) => $a !== $action);
        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addPermission($this);
        }
        return $this;
    }

    public function removeRole(Role $role): static
    {
        if ($this->roles->removeElement($role)) {
            $role->removePermission($this);
        }
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'module' => $this->module,
            'name' => $this->name,
            'description' => $this->description,
            'actions' => $this->actions,
        ];
    }
}
