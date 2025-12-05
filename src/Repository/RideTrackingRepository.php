<?php

namespace App\Repository;

use App\Entity\RideTracking;
use App\Entity\Ride;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RideTracking>
 */
class RideTrackingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RideTracking::class);
    }

    /**
     * Récupère les positions d'une course
     */
    public function findByRide(Ride $ride, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.ride = :ride')
            ->setParameter('ride', $ride)
            ->orderBy('t.timestamp', 'ASC');
        
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère la dernière position d'une course
     */
    public function findLastPosition(Ride $ride): ?RideTracking
    {
        return $this->createQueryBuilder('t')
            ->where('t.ride = :ride')
            ->setParameter('ride', $ride)
            ->orderBy('t.timestamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les positions depuis un timestamp donné
     */
    public function findSince(Ride $ride, \DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.ride = :ride')
            ->andWhere('t.timestamp > :since')
            ->setParameter('ride', $ride)
            ->setParameter('since', $since)
            ->orderBy('t.timestamp', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime les anciennes positions (plus de 24h après course terminée)
     */
    public function cleanupOldPositions(): int
    {
        $threshold = new \DateTime('-24 hours');
        
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.timestamp < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }
}
