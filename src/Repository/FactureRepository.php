<?php

namespace App\Repository;

use App\Entity\Facture;
use App\Entity\Chauffeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facture>
 */
class FactureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Facture::class);
    }

    /**
     * Trouver les factures émises par un chauffeur
     */
    public function findByEmetteur(Chauffeur $chauffeur): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.emetteur = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->orderBy('f.dateEmission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les factures reçues par un chauffeur
     */
    public function findByDestinataire(Chauffeur $chauffeur): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.destinataire = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->orderBy('f.dateEmission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver toutes les factures d'un chauffeur (émises + reçues)
     */
    public function findAllByChaufffeur(Chauffeur $chauffeur): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.emetteur = :chauffeur OR f.destinataire = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->orderBy('f.dateEmission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les factures par course
     */
    public function findByCourse(int $courseId): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.course = :courseId')
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les factures en attente de paiement
     */
    public function findPendingByDestinataire(Chauffeur $chauffeur): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.destinataire = :chauffeur')
            ->andWhere('f.statut = :statut')
            ->setParameter('chauffeur', $chauffeur)
            ->setParameter('statut', Facture::STATUT_ISSUED)
            ->orderBy('f.dateEmission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les factures par année
     */
    public function countByYear(int $year): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('YEAR(f.dateEmission) = :year')
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calculer le chiffre d'affaires d'un chauffeur (factures émises payées)
     */
    public function calculateTotalEmis(Chauffeur $chauffeur, ?int $year = null): float
    {
        $qb = $this->createQueryBuilder('f')
            ->select('SUM(f.montantTTC)')
            ->where('f.emetteur = :chauffeur')
            ->andWhere('f.statut = :statut')
            ->setParameter('chauffeur', $chauffeur)
            ->setParameter('statut', Facture::STATUT_PAID);

        if ($year) {
            $qb->andWhere('YEAR(f.dateEmission) = :year')
                ->setParameter('year', $year);
        }

        return floatval($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    /**
     * Calculer le total des factures reçues (dépenses)
     */
    public function calculateTotalRecu(Chauffeur $chauffeur, ?int $year = null): float
    {
        $qb = $this->createQueryBuilder('f')
            ->select('SUM(f.montantTTC)')
            ->where('f.destinataire = :chauffeur')
            ->andWhere('f.statut = :statut')
            ->setParameter('chauffeur', $chauffeur)
            ->setParameter('statut', Facture::STATUT_PAID);

        if ($year) {
            $qb->andWhere('YEAR(f.dateEmission) = :year')
                ->setParameter('year', $year);
        }

        return floatval($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    /**
     * Statistiques de facturation
     */
    public function getStats(Chauffeur $chauffeur): array
    {
        $emises = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.emetteur = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->getQuery()
            ->getSingleScalarResult();

        $recues = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.destinataire = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->getQuery()
            ->getSingleScalarResult();

        $enAttente = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.destinataire = :chauffeur')
            ->andWhere('f.statut = :statut')
            ->setParameter('chauffeur', $chauffeur)
            ->setParameter('statut', Facture::STATUT_ISSUED)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'facturesEmises' => (int) $emises,
            'facturesRecues' => (int) $recues,
            'facturesEnAttente' => (int) $enAttente,
            'totalEmis' => $this->calculateTotalEmis($chauffeur),
            'totalRecu' => $this->calculateTotalRecu($chauffeur),
        ];
    }
}
