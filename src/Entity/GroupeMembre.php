<?php

namespace App\Entity;

use App\Repository\GroupeMembreRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Membre d'un groupe de confiance
 */
#[ORM\Entity(repositoryClass: GroupeMembreRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_membre_groupe', columns: ['groupe_id', 'chauffeur_id'])]
class GroupeMembre
{
    public const ROLE_MEMBRE = 'membre';
    public const ROLE_ADMIN = 'admin';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Groupe::class, inversedBy: 'membres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Groupe $groupe = null;

    #[ORM\ManyToOne(targetEntity: Chauffeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chauffeur $chauffeur = null;

    #[ORM\Column(length: 20)]
    private string $role = self::ROLE_MEMBRE;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\ManyToOne(targetEntity: Chauffeur::class)]
    private ?Chauffeur $invitePar = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setGroupe(?Groupe $groupe): static
    {
        $this->groupe = $groupe;
        return $this;
    }

    public function getChauffeur(): ?Chauffeur
    {
        return $this->chauffeur;
    }

    public function setChauffeur(?Chauffeur $chauffeur): static
    {
        $this->chauffeur = $chauffeur;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getInvitePar(): ?Chauffeur
    {
        return $this->invitePar;
    }

    public function setInvitePar(?Chauffeur $invitePar): static
    {
        $this->invitePar = $invitePar;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'chauffeur' => [
                'id' => $this->chauffeur?->getId(),
                'name' => $this->chauffeur?->getFullName(),
                'email' => $this->chauffeur?->getEmail(),
                'tel' => $this->chauffeur?->getTel(),
                'company' => $this->chauffeur?->getNomSociete(),
                'img' => '/img/avatars/' . (($this->chauffeur?->getId() ?? 0) % 10 + 1) . '.jpg',
            ],
            'role' => $this->role,
            'joinedAt' => $this->joinedAt?->format('Y-m-d H:i:s'),
            'invitePar' => $this->invitePar ? [
                'id' => $this->invitePar->getId(),
                'name' => $this->invitePar->getFullName(),
            ] : null,
        ];
    }
}
