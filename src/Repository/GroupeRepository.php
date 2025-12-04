<?php

namespace App\Repository;

use App\Entity\Groupe;
use App\Entity\Chauffeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Groupe>
 */
class GroupeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Groupe::class);
    }

    public function save(Groupe $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Groupe $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve un groupe par son code d'invitation
     */
    public function findByCode(string $code): ?Groupe
    {
        return $this->findOneBy(['code' => $code, 'isActive' => true]);
    }

    /**
     * Trouve tous les groupes dont le chauffeur est propriétaire
     */
    public function findByProprietaire(Chauffeur $chauffeur): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.proprietaire = :chauffeur')
            ->andWhere('g.isActive = :active')
            ->setParameter('chauffeur', $chauffeur)
            ->setParameter('active', true)
            ->orderBy('g.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les groupes dont le chauffeur est membre (pas propriétaire)
     */
    public function findByMembre(Chauffeur $chauffeur): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.membres', 'm')
            ->andWhere('m.chauffeur = :chauffeur')
            ->andWhere('g.isActive = :active')
            ->setParameter('chauffeur', $chauffeur)
            ->setParameter('active', true)
            ->orderBy('g.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les groupes auxquels le chauffeur appartient (propriétaire OU membre)
     */
    public function findAllForChauffeur(Chauffeur $chauffeur): array
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.membres', 'm')
            ->andWhere('g.isActive = :active')
            ->andWhere('g.proprietaire = :chauffeur OR m.chauffeur = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->setParameter('active', true)
            ->orderBy('g.nom', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de groupes créés par un chauffeur
     */
    public function countByProprietaire(Chauffeur $chauffeur): int
    {
        return (int) $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->andWhere('g.proprietaire = :chauffeur')
            ->setParameter('chauffeur', $chauffeur)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
