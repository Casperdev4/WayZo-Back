<?php

namespace App\Entity;

use App\Repository\GroupeInvitationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Invitation à rejoindre un groupe de confiance
 */
#[ORM\Entity(repositoryClass: GroupeInvitationRepository::class)]
class GroupeInvitation
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Groupe::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Groupe $groupe = null;

    #[ORM\ManyToOne(targetEntity: Chauffeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chauffeur $invitePar = null;

    #[ORM\ManyToOne(targetEntity: Chauffeur::class)]
    private ?Chauffeur $chauffeurInvite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $token = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable('+7 days');
        $this->token = bin2hex(random_bytes(32));
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

    public function getInvitePar(): ?Chauffeur
    {
        return $this->invitePar;
    }

    public function setInvitePar(?Chauffeur $invitePar): static
    {
        $this->invitePar = $invitePar;
        return $this;
    }

    public function getChauffeurInvite(): ?Chauffeur
    {
        return $this->chauffeurInvite;
    }

    public function setChauffeurInvite(?Chauffeur $chauffeurInvite): static
    {
        $this->chauffeurInvite = $chauffeurInvite;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeImmutable $respondedAt): static
    {
        $this->respondedAt = $respondedAt;
        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function accept(): static
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->respondedAt = new \DateTimeImmutable();
        return $this;
    }

    public function reject(): static
    {
        $this->status = self::STATUS_REJECTED;
        $this->respondedAt = new \DateTimeImmutable();
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'groupe' => [
                'id' => $this->groupe?->getId(),
                'nom' => $this->groupe?->getNom(),
            ],
            'invitePar' => [
                'id' => $this->invitePar?->getId(),
                'name' => $this->invitePar?->getFullName(),
            ],
            'chauffeurInvite' => $this->chauffeurInvite ? [
                'id' => $this->chauffeurInvite->getId(),
                'name' => $this->chauffeurInvite->getFullName(),
                'email' => $this->chauffeurInvite->getEmail(),
            ] : null,
            'email' => $this->email,
            'status' => $this->status,
            'message' => $this->message,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'expiresAt' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'isExpired' => $this->isExpired(),
        ];
    }
}
