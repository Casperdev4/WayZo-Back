<?php

namespace App\Entity;

use App\Repository\ChauffeurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


#[ORM\Entity(repositoryClass: ChauffeurRepository::class)]
class Chauffeur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $nom = null;

    #[ORM\Column(length: 30)]
    private ?string $prenom = null;

    #[ORM\Column(length: 12)]
    private ?string $tel = null;

    #[ORM\Column(length: 100)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $siret = null;

    #[ORM\Column(length: 100)]
    private ?string $nomSociete = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $permis = null;

    #[ORM\Column(length: 100)]
    private ?string $kbis = null;

    #[ORM\Column(length: 100)]
    private ?string $carteVtc = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $vehicle = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateNaissance = null;


    /**
     * @var Collection<int, Course>
     */
    #[ORM\OneToMany(targetEntity: Course::class, mappedBy: 'chauffeurVendeur')]
    private Collection $coursesVendues;

    /**
     * @var Collection<int, Course>
     */
    #[ORM\OneToMany(targetEntity: Course::class, mappedBy: 'chauffeurAccepteur')]
    private Collection $coursesAcceptees;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'expediteur')]
    private Collection $messagesEnvoyes;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'chauffeurPayeur')]
    private Collection $transactionsPayees;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'chauffeurReceveur')]
    private Collection $transactionsRecues;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'favorisDe')]
    private Collection $favoris;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'favoris')]
    private Collection $favorisDe;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\Column(length: 30)]
    private ?string $ville = null;

    #[ORM\Column(length: 8)]
    private ?string $codePostal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $macaron = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pieceIdentite = null;

    public function __construct()
    {
        $this->coursesVendues = new ArrayCollection();
        $this->coursesAcceptees = new ArrayCollection();
        $this->messagesEnvoyes = new ArrayCollection();
        $this->transactionsPayees = new ArrayCollection();
        $this->transactionsRecues = new ArrayCollection();
        $this->favoris = new ArrayCollection();
        $this->favorisDe = new ArrayCollection();
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(string $tel): static
    {
        $this->tel = $tel;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getNomSociete(): ?string
    {
        return $this->nomSociete;
    }

    public function setNomSociete(string $nomSociete): static
    {
        $this->nomSociete = $nomSociete;

        return $this;
    }

    public function getPermis(): ?string
    {
        return $this->permis;
    }

    public function setPermis(string $permis): static
    {
        $this->permis = $permis;

        return $this;
    }

    public function getKbis(): ?string
    {
        return $this->kbis;
    }

    public function setKbis(string $kbis): static
    {
        $this->kbis = $kbis;

        return $this;
    }

    public function getCarteVtc(): ?string
    {
        return $this->carteVtc;
    }

    public function setCarteVtc(string $carteVtc): static
    {
        $this->carteVtc = $carteVtc;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getVehicle(): ?string
    {
        return $this->vehicle;
    }

    public function setVehicle(?string $vehicle): static
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeImmutable
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeImmutable $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    /**
     * @return Collection<int, Course>
     */
    public function getCoursesVendues(): Collection
    {
        return $this->coursesVendues;
    }

    public function addCoursesVendue(Course $coursesVendue): static
    {
        if (!$this->coursesVendues->contains($coursesVendue)) {
            $this->coursesVendues->add($coursesVendue);
            $coursesVendue->setChauffeurVendeur($this);
        }

        return $this;
    }

    public function removeCoursesVendue(Course $coursesVendue): static
    {
        if ($this->coursesVendues->removeElement($coursesVendue)) {
            // set the owning side to null (unless already changed)
            if ($coursesVendue->getChauffeurVendeur() === $this) {
                $coursesVendue->setChauffeurVendeur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Course>
     */
    public function getCoursesAcceptees(): Collection
    {
        return $this->coursesAcceptees;
    }

    public function addCoursesAcceptee(Course $coursesAcceptee): static
    {
        if (!$this->coursesAcceptees->contains($coursesAcceptee)) {
            $this->coursesAcceptees->add($coursesAcceptee);
            $coursesAcceptee->setChauffeurAccepteur($this);
        }

        return $this;
    }

    public function removeCoursesAcceptee(Course $coursesAcceptee): static
    {
        if ($this->coursesAcceptees->removeElement($coursesAcceptee)) {
            // set the owning side to null (unless already changed)
            if ($coursesAcceptee->getChauffeurAccepteur() === $this) {
                $coursesAcceptee->setChauffeurAccepteur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessagesEnvoyes(): Collection
    {
        return $this->messagesEnvoyes;
    }

    public function addMessagesEnvoye(Message $messagesEnvoye): static
    {
        if (!$this->messagesEnvoyes->contains($messagesEnvoye)) {
            $this->messagesEnvoyes->add($messagesEnvoye);
            $messagesEnvoye->setExpediteur($this);
        }

        return $this;
    }

    public function removeMessagesEnvoye(Message $messagesEnvoye): static
    {
        if ($this->messagesEnvoyes->removeElement($messagesEnvoye)) {
            // set the owning side to null (unless already changed)
            if ($messagesEnvoye->getExpediteur() === $this) {
                $messagesEnvoye->setExpediteur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionsPayees(): Collection
    {
        return $this->transactionsPayees;
    }

    public function addTransactionsPayee(Transaction $transactionsPayee): static
    {
        if (!$this->transactionsPayees->contains($transactionsPayee)) {
            $this->transactionsPayees->add($transactionsPayee);
            $transactionsPayee->setChauffeurPayeur($this);
        }

        return $this;
    }

    public function removeTransactionsPayee(Transaction $transactionsPayee): static
    {
        if ($this->transactionsPayees->removeElement($transactionsPayee)) {
            // set the owning side to null (unless already changed)
            if ($transactionsPayee->getChauffeurPayeur() === $this) {
                $transactionsPayee->setChauffeurPayeur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactionsRecues(): Collection
    {
        return $this->transactionsRecues;
    }

    public function addTransactionsRecue(Transaction $transactionsRecue): static
    {
        if (!$this->transactionsRecues->contains($transactionsRecue)) {
            $this->transactionsRecues->add($transactionsRecue);
            $transactionsRecue->setChauffeurReceveur($this);
        }

        return $this;
    }

    public function removeTransactionsRecue(Transaction $transactionsRecue): static
    {
        if ($this->transactionsRecues->removeElement($transactionsRecue)) {
            // set the owning side to null (unless already changed)
            if ($transactionsRecue->getChauffeurReceveur() === $this) {
                $transactionsRecue->setChauffeurReceveur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFavoris(): Collection
    {
        return $this->favoris;
    }

    public function addFavori(self $favori): static
    {
        if (!$this->favoris->contains($favori)) {
            $this->favoris->add($favori);
        }

        return $this;
    }

    public function removeFavori(self $favori): static
    {
        $this->favoris->removeElement($favori);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFavorisDe(): Collection
    {
        return $this->favorisDe;
    }

    public function addFavorisDe(self $favorisDe): static
    {
        if (!$this->favorisDe->contains($favorisDe)) {
            $this->favorisDe->add($favorisDe);
            $favorisDe->addFavori($this);
        }

        return $this;
    }

    public function removeFavorisDe(self $favorisDe): static
    {
        if ($this->favorisDe->removeElement($favorisDe)) {
            $favorisDe->removeFavori($this);
        }

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Si tu stockes des infos sensibles temporaires, nettoie-les ici.
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): static
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getMacaron(): ?string
    {
        return $this->macaron;
    }

    public function setMacaron(?string $macaron): static
    {
        $this->macaron = $macaron;

        return $this;
    }

    public function getPieceIdentite(): ?string
    {
        return $this->pieceIdentite;
    }

    public function setPieceIdentite(?string $pieceIdentite): static
    {
        $this->pieceIdentite = $pieceIdentite;

        return $this;
    }

}
