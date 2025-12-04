<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{
    // Rôles système par défaut
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_CHAUFFEUR = 'ROLE_USER';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isSystem = false;

    #[ORM\Column(type: 'json')]
    private array $accessRights = [];

    #[ORM\ManyToMany(targetEntity: Permission::class, inversedBy: 'roles')]
    #[ORM\JoinTable(name: 'role_permissions')]
    private Collection $permissions;

    #[ORM\ManyToMany(targetEntity: Chauffeur::class, mappedBy: 'customRoles')]
    private Collection $users;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem): static
    {
        $this->isSystem = $isSystem;
        return $this;
    }

    public function getAccessRights(): array
    {
        return $this->accessRights;
    }

    public function setAccessRights(array $accessRights): static
    {
        $this->accessRights = $accessRights;
        return $this;
    }

    /**
     * Définit les droits d'accès pour un module spécifique
     */
    public function setModuleAccess(string $module, array $actions): static
    {
        $this->accessRights[$module] = $actions;
        return $this;
    }

    /**
     * Obtient les droits d'accès pour un module
     */
    public function getModuleAccess(string $module): array
    {
        return $this->accessRights[$module] ?? [];
    }

    /**
     * Vérifie si le rôle a une action spécifique sur un module
     */
    public function hasAccess(string $module, string $action): bool
    {
        $moduleAccess = $this->getModuleAccess($module);
        return in_array($action, $moduleAccess, true);
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }
        return $this;
    }

    public function removePermission(Permission $permission): static
    {
        $this->permissions->removeElement($permission);
        return $this;
    }

    /**
     * @return Collection<int, Chauffeur>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(Chauffeur $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }
        return $this;
    }

    public function removeUser(Chauffeur $user): static
    {
        $this->users->removeElement($user);
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function toArray(): array
    {
        $users = [];
        foreach ($this->users as $user) {
            $users[] = [
                'id' => $user->getId(),
                'name' => $user->getPrenom() . ' ' . $user->getNom(),
                'img' => '/img/avatars/' . ($user->getId() % 10 + 1) . '.jpg',
            ];
        }

        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'isSystem' => $this->isSystem,
            'accessRight' => $this->accessRights,
            'users' => $users,
            'userCount' => count($users),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
