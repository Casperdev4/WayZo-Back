<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Chauffeur qui a créé la course
     */
    #[ORM\ManyToOne(targetEntity: Chauffeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chauffeur $chauffeur1 = null;

    /**
     * Chauffeur qui a accepté la course
     */
    #[ORM\ManyToOne(targetEntity: Chauffeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chauffeur $chauffeur2 = null;

    /**
     * Course liée à cette conversation
     */
    #[ORM\ManyToOne(targetEntity: Ride::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ride $ride = null;

    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: Message::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['dateEnvoi' => 'ASC'])]
    private Collection $messages;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastMessageAt = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChauffeur1(): ?Chauffeur
    {
        return $this->chauffeur1;
    }

    public function setChauffeur1(?Chauffeur $chauffeur1): static
    {
        $this->chauffeur1 = $chauffeur1;
        return $this;
    }

    public function getChauffeur2(): ?Chauffeur
    {
        return $this->chauffeur2;
    }

    public function setChauffeur2(?Chauffeur $chauffeur2): static
    {
        $this->chauffeur2 = $chauffeur2;
        return $this;
    }

    public function getRide(): ?Ride
    {
        return $this->ride;
    }

    public function setRide(?Ride $ride): static
    {
        $this->ride = $ride;
        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLastMessageAt(): ?\DateTimeInterface
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(?\DateTimeInterface $lastMessageAt): static
    {
        $this->lastMessageAt = $lastMessageAt;
        return $this;
    }

    /**
     * Vérifie si un chauffeur fait partie de la conversation
     */
    public function hasParticipant(Chauffeur $chauffeur): bool
    {
        return $this->chauffeur1 === $chauffeur || $this->chauffeur2 === $chauffeur;
    }

    /**
     * Récupère l'autre participant
     */
    public function getOtherParticipant(Chauffeur $chauffeur): ?Chauffeur
    {
        if ($this->chauffeur1 === $chauffeur) {
            return $this->chauffeur2;
        }
        if ($this->chauffeur2 === $chauffeur) {
            return $this->chauffeur1;
        }
        return null;
    }
}
