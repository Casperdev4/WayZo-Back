<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use App\Entity\Chauffeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Récupérer les logs d'un chauffeur avec pagination
     */
    public function findByUser(Chauffeur $chauffeur, int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.chauffeur = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer tous les logs (admin) avec pagination
     */
    public function findAllPaginated(int $page = 1, int $limit = 20, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($type) {
            $qb->where('a.type = :type')
               ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compter les logs d'un utilisateur
     */
    public function countByUser(Chauffeur $chauffeur): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.chauffeur = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupérer les dernières connexions
     */
    public function findLastLogins(Chauffeur $chauffeur, int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.chauffeur = :chauffeur')
            ->andWhere('a.type = :type')
            ->setParameter('chauffeur', $chauffeur)
            ->setParameter('type', ActivityLog::TYPE_LOGIN)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Grouper les logs par date
     */
    public function findGroupedByDate(Chauffeur $chauffeur, int $page = 1): array
    {
        $logs = $this->findByUser($chauffeur, $page, 50);
        
        $grouped = [];
        foreach ($logs as $log) {
            $dateKey = $log->getCreatedAt()->format('Y-m-d');
            if (!isset($grouped[$dateKey])) {
                $grouped[$dateKey] = [
                    'date' => $log->getCreatedAt()->getTimestamp(),
                    'events' => [],
                ];
            }
            $grouped[$dateKey]['events'][] = $log;
        }
        
        return array_values($grouped);
    }
}
