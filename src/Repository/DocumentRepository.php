<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\Chauffeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function save(Document $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Document $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve tous les documents d'un chauffeur
     */
    public function findByChauffeur(Chauffeur $chauffeur, ?string $type = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.chauffeur = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->orderBy('d.createdAt', 'DESC');

        if ($type) {
            $qb->andWhere('d.type = :type')
               ->setParameter('type', $type);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les documents par statut
     */
    public function findByStatus(string $status, ?Chauffeur $chauffeur = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.status = :status')
            ->setParameter('status', $status)
            ->orderBy('d.createdAt', 'DESC');

        if ($chauffeur) {
            $qb->andWhere('d.chauffeur = :chauffeur')
               ->setParameter('chauffeur', $chauffeur);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve tous les documents en attente de validation
     */
    public function findPendingDocuments(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.status = :status')
            ->setParameter('status', Document::STATUS_PENDING)
            ->orderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les documents qui vont expirer bientôt
     */
    public function findExpiringDocuments(int $daysBeforeExpiry = 30): array
    {
        $expiryDate = new \DateTimeImmutable("+{$daysBeforeExpiry} days");

        return $this->createQueryBuilder('d')
            ->where('d.expiresAt IS NOT NULL')
            ->andWhere('d.expiresAt <= :expiryDate')
            ->andWhere('d.expiresAt > :now')
            ->andWhere('d.status = :status')
            ->setParameter('expiryDate', $expiryDate)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('status', Document::STATUS_APPROVED)
            ->orderBy('d.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les documents expirés
     */
    public function findExpiredDocuments(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.expiresAt IS NOT NULL')
            ->andWhere('d.expiresAt < :now')
            ->andWhere('d.status != :expired')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('expired', Document::STATUS_EXPIRED)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un document par son token de partage
     */
    public function findByShareToken(string $token): ?Document
    {
        return $this->createQueryBuilder('d')
            ->where('d.shareToken = :token')
            ->andWhere('d.isShared = true')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les documents par type pour un chauffeur
     */
    public function countByTypeForChauffeur(Chauffeur $chauffeur): array
    {
        $results = $this->createQueryBuilder('d')
            ->select('d.type, d.status, COUNT(d.id) as count')
            ->where('d.chauffeur = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->groupBy('d.type, d.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            if (!isset($counts[$row['type']])) {
                $counts[$row['type']] = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
            }
            $counts[$row['type']]['total'] += $row['count'];
            $counts[$row['type']][$row['status']] = $row['count'];
        }

        return $counts;
    }

    /**
     * Statistiques globales des documents
     */
    public function getStats(): array
    {
        $results = $this->createQueryBuilder('d')
            ->select('d.status, COUNT(d.id) as count')
            ->groupBy('d.status')
            ->getQuery()
            ->getResult();

        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'expired' => 0,
        ];

        foreach ($results as $row) {
            $stats[$row['status']] = $row['count'];
            $stats['total'] += $row['count'];
        }

        // Documents expirant bientôt
        $stats['expiringSoon'] = count($this->findExpiringDocuments(30));

        return $stats;
    }

    /**
     * Recherche de documents avec pagination
     */
    public function searchWithPagination(
        int $page = 1,
        int $limit = 20,
        ?string $search = null,
        ?string $type = null,
        ?string $status = null,
        ?Chauffeur $chauffeur = null
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.chauffeur', 'c');

        if ($search) {
            $qb->andWhere('d.originalName LIKE :search OR c.nom LIKE :search OR c.prenom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($type) {
            $qb->andWhere('d.type = :type')
               ->setParameter('type', $type);
        }

        if ($status) {
            $qb->andWhere('d.status = :status')
               ->setParameter('status', $status);
        }

        if ($chauffeur) {
            $qb->andWhere('d.chauffeur = :chauffeur')
               ->setParameter('chauffeur', $chauffeur);
        }

        // Compte total
        $countQb = clone $qb;
        $total = $countQb->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();

        // Pagination
        $documents = $qb->orderBy('d.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'data' => $documents,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
        ];
    }
}
