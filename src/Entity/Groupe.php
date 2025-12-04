<?php

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Groupe de confiance entre chauffeurs
 * Permet de proposer des courses uniquement aux membres du groupe
 */
#[ORM\Entity(repositoryClass: GroupeRepository::class)]
class Groupe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Chauffeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chauffeur $proprietaire = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\OneToMany(targetEntity: GroupeMembre::class, mappedBy: 'groupe', orphanRemoval: true)]
    private Collection $membres;

    #[ORM\OneToMany(targetEntity: GroupeInvitation::class, mappedBy: 'groupe', orphanRemoval: true)]
    private Collection $invitations;

    #[ORM\OneToMany(targetEntity: Course::class, mappedBy: 'groupe')]
    private Collection $courses;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->membres = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->code = $this->generateCode();
    }

    private function generateCode(): string
    {
        return strtoupper(substr(md5(uniqid()), 0, 8));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
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

    public function getProprietaire(): ?Chauffeur
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?Chauffeur $proprietaire): static
    {
        $this->proprietaire = $proprietaire;
        return $this;
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

    public function regenerateCode(): static
    {
        $this->code = $this->generateCode();
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * @return Collection<int, GroupeMembre>
     */
    public function getMembres(): Collection
    {
        return $this->membres;
    }

    public function addMembre(GroupeMembre $membre): static
    {
        if (!$this->membres->contains($membre)) {
            $this->membres->add($membre);
            $membre->setGroupe($this);
        }
        return $this;
    }

    public function removeMembre(GroupeMembre $membre): static
    {
        if ($this->membres->removeElement($membre)) {
            if ($membre->getGroupe() === $this) {
                $membre->setGroupe(null);
            }
        }
        return $this;
    }

    /**
     * Vérifie si un chauffeur est membre du groupe
     */
    public function hasMembre(Chauffeur $chauffeur): bool
    {
        // Le propriétaire est toujours membre
        if ($this->proprietaire && $this->proprietaire->getId() === $chauffeur->getId()) {
            return true;
        }

        foreach ($this->membres as $membre) {
            if ($membre->getChauffeur()->getId() === $chauffeur->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Collection<int, GroupeInvitation>
     */
    public function getInvitations(): Collection
    {
        return $this->invitations;
    }

    /**
     * @return Collection<int, Course>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
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

    /**
     * Compte le nombre de membres (incluant le propriétaire)
     */
    public function getMembresCount(): int
    {
        return $this->membres->count() + 1; // +1 pour le propriétaire
    }

    public function toArray(): array
    {
        $membresData = [];
        foreach ($this->membres as $membre) {
            $membresData[] = $membre->toArray();
        }

        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'code' => $this->code,
            'isActive' => $this->isActive,
            'proprietaire' => [
                'id' => $this->proprietaire?->getId(),
                'name' => $this->proprietaire?->getFullName(),
                'img' => '/img/avatars/' . (($this->proprietaire?->getId() ?? 0) % 10 + 1) . '.jpg',
            ],
            'membres' => $membresData,
            'membresCount' => $this->getMembresCount(),
            'coursesCount' => $this->courses->count(),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
