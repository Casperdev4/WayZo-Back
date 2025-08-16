<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $montant = null;

    #[ORM\Column]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 30)]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'transactionsPayees')]
    private ?Chauffeur $chauffeurPayeur = null;

    #[ORM\ManyToOne(inversedBy: 'transactionsRecues')]
    private ?Chauffeur $chauffeurReceveur = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?Course $course = null;

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
}

