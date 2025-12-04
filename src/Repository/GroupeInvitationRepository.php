<?php

namespace App\Repository;

use App\Entity\GroupeInvitation;
use App\Entity\Groupe;
use App\Entity\Chauffeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupeInvitation>
 */
class GroupeInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupeInvitation::class);
    }

    public function save(GroupeInvitation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GroupeInvitation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve une invitation par son token
     */
    public function findByToken(string $token): ?GroupeInvitation
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * Trouve les invitations en attente pour un chauffeur
     */
    public function findPendingForChauffeur(Chauffeur $chauffeur): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.chauffeurInvite = :chauffeur')
            ->andWhere('i.status = :status')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('chauffeur', $chauffeur)
            ->setParameter('status', GroupeInvitation::STATUS_PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les invitations en attente par email
     */
    public function findPendingByEmail(string $email): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.email = :email')
            ->andWhere('i.status = :status')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('email', $email)
            ->setParameter('status', GroupeInvitation::STATUS_PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les invitations d'un groupe
     */
    public function findByGroupe(Groupe $groupe, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('i')
            ->andWhere('i.groupe = :groupe')
            ->setParameter('groupe', $groupe)
            ->orderBy('i.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Vérifie si une invitation existe déjà pour ce chauffeur/email dans ce groupe
     */
    public function hasPendingInvitation(Groupe $groupe, ?Chauffeur $chauffeur = null, ?string $email = null): bool
    {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.groupe = :groupe')
            ->andWhere('i.status = :status')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('groupe', $groupe)
            ->setParameter('status', GroupeInvitation::STATUS_PENDING)
            ->setParameter('now', new \DateTimeImmutable());

        if ($chauffeur) {
            $qb->andWhere('i.chauffeurInvite = :chauffeur')
               ->setParameter('chauffeur', $chauffeur);
        } elseif ($email) {
            $qb->andWhere('i.email = :email')
               ->setParameter('email', $email);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Expire les invitations anciennes
     */
    public function expireOldInvitations(): int
    {
        return $this->createQueryBuilder('i')
            ->update()
            ->set('i.status', ':expired')
            ->andWhere('i.status = :pending')
            ->andWhere('i.expiresAt < :now')
            ->setParameter('expired', GroupeInvitation::STATUS_EXPIRED)
            ->setParameter('pending', GroupeInvitation::STATUS_PENDING)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
