<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    // Statuts de transaction
    public const STATUT_PENDING = 'pending';           // Argent bloqué (course en cours)
    public const STATUT_COMPLETED = 'completed';       // Argent libéré (course terminée)
    public const STATUT_CANCELLED = 'cancelled';       // Transaction annulée
    public const STATUT_REFUNDED = 'refunded';         // Remboursement effectué

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $montant = null;

    #[ORM\Column]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 30)]
    private ?string $statut = self::STATUT_PENDING;

    #[ORM\ManyToOne(inversedBy: 'transactionsPayees')]
    private ?Chauffeur $chauffeurPayeur = null;

    #[ORM\ManyToOne(inversedBy: 'transactionsRecues')]
    private ?Chauffeur $chauffeurReceveur = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?Course $course = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $reference = null;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->reference = 'TXN-' . strtoupper(uniqid());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getChauffeurPayeur(): ?Chauffeur
    {
        return $this->chauffeurPayeur;
    }

    public function setChauffeurPayeur(?Chauffeur $chauffeurPayeur): static
    {
        $this->chauffeurPayeur = $chauffeurPayeur;
        return $this;
    }

    public function getChauffeurReceveur(): ?Chauffeur
    {
        return $this->chauffeurReceveur;
    }

    public function setChauffeurReceveur(?Chauffeur $chauffeurReceveur): static
    {
        $this->chauffeurReceveur = $chauffeurReceveur;
        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * Vérifie si l'argent est bloqué
     */
    public function isPending(): bool
    {
        return $this->statut === self::STATUT_PENDING;
    }

    /**
     * Vérifie si la transaction est terminée
     */
    public function isCompleted(): bool
    {
        return $this->statut === self::STATUT_COMPLETED;
    }

    /**
     * Libérer l'argent (quand la course est terminée)
     */
    public function complete(): static
    {
        $this->statut = self::STATUT_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Annuler la transaction
     */
    public function cancel(): static
    {
        $this->statut = self::STATUT_CANCELLED;
        return $this;
    }
}

