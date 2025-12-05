<?php

namespace App\Entity;

use App\Repository\RideTrackingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité pour stocker les positions GPS du chauffeur pendant une course
 */
#[ORM\Entity(repositoryClass: RideTrackingRepository::class)]
#[ORM\Index(name: 'idx_ride_tracking_ride', columns: ['ride_id'])]
#[ORM\Index(name: 'idx_ride_tracking_timestamp', columns: ['timestamp'])]
class RideTracking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ride::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Ride $ride = null;

    #[ORM\Column(type: 'float')]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float')]
    private ?float $longitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $speed = null; // Vitesse en km/h

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $heading = null; // Direction en degrés (0-360)

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $accuracy = null; // Précision GPS en mètres

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $timestamp = null;

    public function __construct()
    {
        $this->timestamp = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getSpeed(): ?float
    {
        return $this->speed;
    }

    public function setSpeed(?float $speed): static
    {
        $this->speed = $speed;
        return $this;
    }

    public function getHeading(): ?float
    {
        return $this->heading;
    }

    public function setHeading(?float $heading): static
    {
        $this->heading = $heading;
        return $this;
    }

    public function getAccuracy(): ?float
    {
        return $this->accuracy;
    }

    public function setAccuracy(?float $accuracy): static
    {
        $this->accuracy = $accuracy;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;
        return $this;
    }
}
