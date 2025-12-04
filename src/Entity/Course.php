<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $nomClient = null;

    #[ORM\Column(length: 200)]
    private ?string $depart = null;

    #[ORM\Column(length: 200)]
    private ?string $arrivee = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $heure = null;

    #[ORM\Column(length: 10)]
    private ?string $prix = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'coursesVendues')]
    private ?Chauffeur $chauffeurVendeur = null;

    #[ORM\ManyToOne(inversedBy: 'coursesAcceptees')]
    private ?Chauffeur $chauffeurAccepteur = null;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'course')]
    private Collection $messages;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'course')]
    private Collection $transactions;

    #[ORM\Column(length: 30)]
    private ?string $statutExecution = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $departVersClient = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $ClientPrisEnCharge = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $ArriveeDestination = null;

    #[ORM\Column(length: 30)]
    private ?string $clientContact = null;

    #[ORM\Column(length: 10)]
    private ?string $passagers = null;

    #[ORM\Column]
    private ?int $bagages = null;

    #[ORM\Column(length: 50)]
    private ?string $vehicule = null;

    #[ORM\Column]
    private ?int $boosterSeat = null;

    #[ORM\Column]
    private ?int $babySeat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Groupe de confiance pour les courses privées
     * Si null = course publique visible par tous
     */
    #[ORM\ManyToOne(targetEntity: Groupe::class, inversedBy: 'courses')]
    private ?Groupe $groupe = null;

    /**
     * Visibilité de la course
     * public = tous les chauffeurs
     * private = uniquement les membres du groupe
     */
    #[ORM\Column(length: 20)]
    private string $visibility = 'public';

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomClient(): ?string
    {
        return $this->nomClient;
    }

    public function setNomClient(string $nomClient): static
    {
        $this->nomClient = $nomClient;

        return $this;
    }

    public function getDepart(): ?string
    {
        return $this->depart;
    }

    public function setDepart(string $depart): static
    {
        $this->depart = $depart;

        return $this;
    }

    public function getArrivee(): ?string
    {
        return $this->arrivee;
    }

    public function setArrivee(string $arrivee): static
    {
        $this->arrivee = $arrivee;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getHeure(): ?\DateTime
    {
        return $this->heure;
    }

    public function setHeure(\DateTime $heure): static
    {
        $this->heure = $heure;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

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

    public function getChauffeurVendeur(): ?Chauffeur
    {
        return $this->chauffeurVendeur;
    }

    public function setChauffeurVendeur(?Chauffeur $chauffeurVendeur): static
    {
        $this->chauffeurVendeur = $chauffeurVendeur;

        return $this;
    }

    public function getChauffeurAccepteur(): ?Chauffeur
    {
        return $this->chauffeurAccepteur;
    }

    public function setChauffeurAccepteur(?Chauffeur $chauffeurAccepteur): static
    {
        $this->chauffeurAccepteur = $chauffeurAccepteur;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setCourse($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getCourse() === $this) {
                $message->setCourse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setCourse($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCourse() === $this) {
                $transaction->setCourse(null);
            }
        }

        return $this;
    }

    public function getStatutExecution(): ?string
    {
        return $this->statutExecution;
    }

    public function setStatutExecution(string $statutExecution): static
    {
        $this->statutExecution = $statutExecution;

        return $this;
    }

    public function getDepartVersClient(): ?\DateTime
    {
        return $this->departVersClient;
    }

    public function setDepartVersClient(?\DateTime $departVersClient): static
    {
        $this->departVersClient = $departVersClient;

        return $this;
    }

    public function getClientPrisEnCharge(): ?\DateTime
    {
        return $this->ClientPrisEnCharge;
    }

    public function setClientPrisEnCharge(?\DateTime $ClientPrisEnCharge): static
    {
        $this->ClientPrisEnCharge = $ClientPrisEnCharge;

        return $this;
    }

    public function getArriveeDestination(): ?\DateTime
    {
        return $this->ArriveeDestination;
    }

    public function setArriveeDestination(?\DateTime $ArriveeDestination): static
    {
        $this->ArriveeDestination = $ArriveeDestination;

        return $this;
    }

    public function getClientContact(): ?string
    {
        return $this->clientContact;
    }

    public function setClientContact(string $clientContact): static
    {
        $this->clientContact = $clientContact;

        return $this;
    }

    public function getPassagers(): ?int
    {
        return $this->passagers;
    }

    public function setPassagers(?int $passagers): static
    {
        $this->passagers = $passagers;

        return $this;
    }

    public function getBagages(): ?int
    {
        return $this->bagages;
    }

    public function setBagages(int $bagages): static
    {
        $this->bagages = $bagages;

        return $this;
    }

    public function getVehicule(): ?string
    {
        return $this->vehicule;
    }

    public function setVehicule(string $vehicule): static
    {
        $this->vehicule = $vehicule;

        return $this;
    }

    public function getBoosterSeat(): ?int
    {
        return $this->boosterSeat;
    }

    public function setBoosterSeat(int $boosterSeat): static
    {
        $this->boosterSeat = $boosterSeat;

        return $this;
    }

    public function getBabySeat(): ?int
    {
        return $this->babySeat;
    }


    public function setBabySeat(int $babySeat): static
    {
        $this->babySeat = $babySeat;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

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

    public function getGroupe(): ?Groupe
    {
        return $this->groupe;
    }

    public function setGroupe(?Groupe $groupe): static
    {
        $this->groupe = $groupe;
        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isPrivate(): bool
    {
        return $this->visibility === 'private' && $this->groupe !== null;
    }

    /**
     * Vérifie si un chauffeur peut voir cette course
     */
    public function isVisibleBy(Chauffeur $chauffeur): bool
    {
        // Courses publiques visibles par tous
        if ($this->isPublic()) {
            return true;
        }

        // Le vendeur peut toujours voir sa course
        if ($this->chauffeurVendeur && $this->chauffeurVendeur->getId() === $chauffeur->getId()) {
            return true;
        }

        // Si course privée avec groupe, vérifier l'appartenance
        if ($this->groupe) {
            return $this->groupe->hasMembre($chauffeur);
        }

        return false;
    }
}
