<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * Récupère toutes les transactions d'un utilisateur (payeur ou receveur)
     * @return Transaction[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.chauffeurPayeur', 'payeur')
            ->leftJoin('t.chauffeurReceveur', 'receveur')
            ->where('payeur.id = :userId OR receveur.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les transactions avec filtres avancés pour l'export
     * @return Transaction[]
     */
    public function findByUserWithFilters(
        int $userId,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
        ?string $statut = null,
        ?string $type = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.chauffeurPayeur', 'payeur')
            ->leftJoin('t.chauffeurReceveur', 'receveur')
            ->leftJoin('t.course', 'course')
            ->addSelect('payeur', 'receveur', 'course');

        // Filtrer par type (sent/received) ou tous
        if ($type === 'sent') {
            $qb->where('payeur.id = :userId')
               ->setParameter('userId', $userId);
        } elseif ($type === 'received') {
            $qb->where('receveur.id = :userId')
               ->setParameter('userId', $userId);
        } else {
            $qb->where('payeur.id = :userId OR receveur.id = :userId')
               ->setParameter('userId', $userId);
        }

        // Filtre date de début
        if ($dateFrom) {
            $qb->andWhere('t.date >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        // Filtre date de fin
        if ($dateTo) {
            // Inclure toute la journée de fin
            $dateToEnd = \DateTime::createFromInterface($dateTo)->setTime(23, 59, 59);
            $qb->andWhere('t.date <= :dateTo')
               ->setParameter('dateTo', $dateToEnd);
        }

        // Filtre statut
        if ($statut) {
            $qb->andWhere('t.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return $qb->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques mensuelles pour un utilisateur
     * @return array
     */
    public function getMonthlyStats(int $userId, int $year): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                MONTH(t.date) as month,
                SUM(CASE WHEN cp.id = :userId THEN t.montant ELSE 0 END) as total_sent,
                SUM(CASE WHEN cr.id = :userId THEN t.montant ELSE 0 END) as total_received,
                COUNT(CASE WHEN cp.id = :userId THEN 1 END) as count_sent,
                COUNT(CASE WHEN cr.id = :userId THEN 1 END) as count_received
            FROM transaction t
            LEFT JOIN chauffeur cp ON t.chauffeur_payeur_id = cp.id
            LEFT JOIN chauffeur cr ON t.chauffeur_receveur_id = cr.id
            WHERE YEAR(t.date) = :year
            AND (cp.id = :userId OR cr.id = :userId)
            GROUP BY MONTH(t.date)
            ORDER BY month
        ";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'userId' => $userId,
            'year' => $year
        ]);
        
        return $result->fetchAllAssociative();
    }

    /**
     * Total des transactions par statut pour un utilisateur
     * @return array
     */
    public function getStatutSummary(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.statut, COUNT(t.id) as count, SUM(t.montant) as total')
            ->leftJoin('t.chauffeurPayeur', 'payeur')
            ->leftJoin('t.chauffeurReceveur', 'receveur')
            ->where('payeur.id = :userId OR receveur.id = :userId')
            ->setParameter('userId', $userId)
            ->groupBy('t.statut')
            ->getQuery()
            ->getResult();
    }
}
