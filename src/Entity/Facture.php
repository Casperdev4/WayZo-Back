<?php

namespace App\Entity;

use App\Repository\FactureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Facture pour la facturation entre chauffeurs VTC
 * 
 * Types de facture :
 * - PRESTATION : Facture émise par l'accepteur vers le vendeur (pour services rendus)
 * - SOUS_TRAITANCE : Facture émise par le vendeur vers l'accepteur (pour la sous-traitance)
 */
#[ORM\Entity(repositoryClass: FactureRepository::class)]
class Facture
{
    // Types de facture
    public const TYPE_PRESTATION = 'prestation';       // L'accepteur facture sa prestation
    public const TYPE_SOUS_TRAITANCE = 'sous_traitance'; // Le vendeur facture la sous-traitance

    // Statuts
    public const STATUT_DRAFT = 'draft';
    public const STATUT_ISSUED = 'issued';
    public const STATUT_PAID = 'paid';
    public const STATUT_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Numéro de facture unique (ex: FAC-2025-000001)
     */
    #[ORM\Column(length: 50, unique: true)]
    private ?string $numero = null;

    /**
     * Type de facture
     */
    #[ORM\Column(length: 30)]
    private ?string $type = null;

    /**
     * Date d'émission
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateEmission = null;

    /**
     * Date d'échéance
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateEcheance = null;

    /**
     * Montant HT
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantHT = null;

    /**
     * Taux de TVA (en %)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $tauxTVA = '20.00';

    /**
     * Montant TVA
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantTVA = null;

    /**
     * Montant TTC
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantTTC = null;

    /**
     * Statut de la facture
     */
    #[ORM\Column(length: 20)]
    private ?string $statut = self::STATUT_DRAFT;

    /**
     * Émetteur de la facture (celui qui facture)
     */
    #[ORM\ManyToOne(targetEntity: Chauffeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chauffeur $emetteur = null;

    /**
     * Destinataire de la facture (celui qui paye)
     */
    #[ORM\ManyToOne(targetEntity: Chauffeur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chauffeur $destinataire = null;

    /**
     * Course liée à cette facture
     */
    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    /**
     * Transaction liée
     */
    #[ORM\ManyToOne(targetEntity: Transaction::class)]
    private ?Transaction $transaction = null;

    /**
     * Description / Libellé de la facture
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Chemin du fichier PDF généré
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    /**
     * Informations société émettrice (snapshot au moment de la création)
     */
    #[ORM\Column(type: Types::JSON)]
    private array $emetteurInfo = [];

    /**
     * Informations société destinataire (snapshot au moment de la création)
     */
    #[ORM\Column(type: Types::JSON)]
    private array $destinataireInfo = [];

    /**
     * Détails de la course (snapshot)
     */
    #[ORM\Column(type: Types::JSON)]
    private array $courseDetails = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->dateEmission = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDateEmission(): ?\DateTime
    {
        return $this->dateEmission;
    }

    public function setDateEmission(\DateTime $dateEmission): static
    {
        $this->dateEmission = $dateEmission;
        return $this;
    }

    public function getDateEcheance(): ?\DateTime
    {
        return $this->dateEcheance;
    }

    public function setDateEcheance(?\DateTime $dateEcheance): static
    {
        $this->dateEcheance = $dateEcheance;
        return $this;
    }

    public function getMontantHT(): ?string
    {
        return $this->montantHT;
    }

    public function setMontantHT(string $montantHT): static
    {
        $this->montantHT = $montantHT;
        $this->calculateTotals();
        return $this;
    }

    public function getTauxTVA(): ?string
    {
        return $this->tauxTVA;
    }

    public function setTauxTVA(string $tauxTVA): static
    {
        $this->tauxTVA = $tauxTVA;
        $this->calculateTotals();
        return $this;
    }

    public function getMontantTVA(): ?string
    {
        return $this->montantTVA;
    }

    public function getMontantTTC(): ?string
    {
        return $this->montantTTC;
    }

    /**
     * Calcule automatiquement TVA et TTC
     */
    private function calculateTotals(): void
    {
        if ($this->montantHT && $this->tauxTVA) {
            $ht = floatval($this->montantHT);
            $taux = floatval($this->tauxTVA);
            $tva = $ht * ($taux / 100);
            $ttc = $ht + $tva;
            
            $this->montantTVA = number_format($tva, 2, '.', '');
            $this->montantTTC = number_format($ttc, 2, '.', '');
        }
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

    public function getEmetteur(): ?Chauffeur
    {
        return $this->emetteur;
    }

    public function setEmetteur(?Chauffeur $emetteur): static
    {
        $this->emetteur = $emetteur;
        return $this;
    }

    public function getDestinataire(): ?Chauffeur
    {
        return $this->destinataire;
    }

    public function setDestinataire(?Chauffeur $destinataire): static
    {
        $this->destinataire = $destinataire;
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

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        $this->transaction = $transaction;
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

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): static
    {
        $this->pdfPath = $pdfPath;
        return $this;
    }

    public function getEmetteurInfo(): array
    {
        return $this->emetteurInfo;
    }

    public function setEmetteurInfo(array $emetteurInfo): static
    {
        $this->emetteurInfo = $emetteurInfo;
        return $this;
    }

    public function getDestinataireInfo(): array
    {
        return $this->destinataireInfo;
    }

    public function setDestinataireInfo(array $destinataireInfo): static
    {
        $this->destinataireInfo = $destinataireInfo;
        return $this;
    }

    public function getCourseDetails(): array
    {
        return $this->courseDetails;
    }

    public function setCourseDetails(array $courseDetails): static
    {
        $this->courseDetails = $courseDetails;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
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
     * Émettre la facture
     */
    public function issue(): static
    {
        $this->statut = self::STATUT_ISSUED;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Marquer comme payée
     */
    public function markAsPaid(): static
    {
        $this->statut = self::STATUT_PAID;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Annuler la facture
     */
    public function cancel(): static
    {
        $this->statut = self::STATUT_CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Vérifie si c'est une facture de prestation
     */
    public function isPrestation(): bool
    {
        return $this->type === self::TYPE_PRESTATION;
    }

    /**
     * Vérifie si c'est une facture de sous-traitance
     */
    public function isSousTraitance(): bool
    {
        return $this->type === self::TYPE_SOUS_TRAITANCE;
    }

    /**
     * Génère un numéro de facture unique
     */
    public static function generateNumero(int $count): string
    {
        $year = date('Y');
        $number = str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        return "FAC-{$year}-{$number}";
    }
}
