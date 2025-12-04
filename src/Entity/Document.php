<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Document uploadé par un chauffeur (permis, carte VTC, KBIS, etc.)
 */
#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Document
{
    // Types de documents
    public const TYPE_PERMIS = 'permis';
    public const TYPE_CARTE_VTC = 'carte_vtc';
    public const TYPE_KBIS = 'kbis';
    public const TYPE_ASSURANCE = 'assurance';
    public const TYPE_CARTE_GRISE = 'carte_grise';
    public const TYPE_CARTE_IDENTITE = 'carte_identite';
    public const TYPE_RIB = 'rib';
    public const TYPE_JUSTIFICATIF_DOMICILE = 'justificatif_domicile';
    public const TYPE_AUTRE = 'autre';

    // Statuts de validation
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    public const ALLOWED_TYPES = [
        self::TYPE_PERMIS,
        self::TYPE_CARTE_VTC,
        self::TYPE_KBIS,
        self::TYPE_ASSURANCE,
        self::TYPE_CARTE_GRISE,
        self::TYPE_CARTE_IDENTITE,
        self::TYPE_RIB,
        self::TYPE_JUSTIFICATIF_DOMICILE,
        self::TYPE_AUTRE,
    ];

    public const TYPE_LABELS = [
        self::TYPE_PERMIS => 'Permis de conduire',
        self::TYPE_CARTE_VTC => 'Carte VTC',
        self::TYPE_KBIS => 'Extrait KBIS',
        self::TYPE_ASSURANCE => 'Attestation d\'assurance',
        self::TYPE_CARTE_GRISE => 'Carte grise',
        self::TYPE_CARTE_IDENTITE => 'Carte d\'identité',
        self::TYPE_RIB => 'RIB',
        self::TYPE_JUSTIFICATIF_DOMICILE => 'Justificatif de domicile',
        self::TYPE_AUTRE => 'Autre document',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Chauffeur::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Chauffeur $chauffeur = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 100)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private ?int $size = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\ManyToOne(targetEntity: Chauffeur::class)]
    private ?Chauffeur $validatedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private bool $isShared = false;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $shareToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $shareExpiresAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getValidatedBy(): ?Chauffeur
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?Chauffeur $validatedBy): static
    {
        $this->validatedBy = $validatedBy;
        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
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

    public function isShared(): bool
    {
        return $this->isShared;
    }

    public function setIsShared(bool $isShared): static
    {
        $this->isShared = $isShared;
        return $this;
    }

    public function getShareToken(): ?string
    {
        return $this->shareToken;
    }

    public function setShareToken(?string $shareToken): static
    {
        $this->shareToken = $shareToken;
        return $this;
    }

    public function getShareExpiresAt(): ?\DateTimeImmutable
    {
        return $this->shareExpiresAt;
    }

    public function setShareExpiresAt(?\DateTimeImmutable $shareExpiresAt): static
    {
        $this->shareExpiresAt = $shareExpiresAt;
        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function isShareExpired(): bool
    {
        if (!$this->isShared || $this->shareExpiresAt === null) {
            return false;
        }
        return $this->shareExpiresAt < new \DateTimeImmutable();
    }

    public function approve(Chauffeur $validator): static
    {
        $this->status = self::STATUS_APPROVED;
        $this->validatedBy = $validator;
        $this->validatedAt = new \DateTimeImmutable();
        $this->rejectionReason = null;
        return $this;
    }

    public function reject(Chauffeur $validator, string $reason): static
    {
        $this->status = self::STATUS_REJECTED;
        $this->validatedBy = $validator;
        $this->validatedAt = new \DateTimeImmutable();
        $this->rejectionReason = $reason;
        return $this;
    }

    public function generateShareToken(int $daysValid = 7): string
    {
        $this->shareToken = bin2hex(random_bytes(32));
        $this->shareExpiresAt = new \DateTimeImmutable("+{$daysValid} days");
        $this->isShared = true;
        return $this->shareToken;
    }

    public function revokeShare(): static
    {
        $this->shareToken = null;
        $this->shareExpiresAt = null;
        $this->isShared = false;
        return $this;
    }

    public function getFilePath(): string
    {
        return 'documents/' . $this->chauffeur->getId() . '/' . $this->filename;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'typeLabel' => $this->getTypeLabel(),
            'originalName' => $this->originalName,
            'filename' => $this->filename,
            'mimeType' => $this->mimeType,
            'size' => $this->size,
            'sizeFormatted' => $this->formatSize($this->size),
            'description' => $this->description,
            'status' => $this->status,
            'rejectionReason' => $this->rejectionReason,
            'validatedAt' => $this->validatedAt?->format('Y-m-d H:i:s'),
            'validatedBy' => $this->validatedBy ? [
                'id' => $this->validatedBy->getId(),
                'name' => $this->validatedBy->getFullName(),
            ] : null,
            'expiresAt' => $this->expiresAt?->format('Y-m-d'),
            'isExpired' => $this->isExpired(),
            'isShared' => $this->isShared,
            'shareToken' => $this->isShared ? $this->shareToken : null,
            'shareExpiresAt' => $this->shareExpiresAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'chauffeur' => [
                'id' => $this->chauffeur?->getId(),
                'name' => $this->chauffeur?->getFullName(),
            ],
        ];
    }

    private function formatSize(int $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / pow(1024, $power), 2, ',', ' ') . ' ' . $units[$power];
    }
}
