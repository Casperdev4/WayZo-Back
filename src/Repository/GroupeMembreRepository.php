<?php

namespace App\Repository;

use App\Entity\GroupeMembre;
use App\Entity\Groupe;
use App\Entity\Chauffeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupeMembre>
 */
class GroupeMembreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupeMembre::class);
    }

    public function save(GroupeMembre $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GroupeMembre $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Vérifie si un chauffeur est déjà membre d'un groupe
     */
    public function isMembre(Groupe $groupe, Chauffeur $chauffeur): bool
    {
        $result = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.groupe = :groupe')
            ->andWhere('m.chauffeur = :chauffeur')
            ->setParameter('groupe', $groupe)
            ->setParameter('chauffeur', $chauffeur)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Trouve tous les membres d'un groupe
     */
    public function findByGroupe(Groupe $groupe): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.groupe = :groupe')
            ->setParameter('groupe', $groupe)
            ->orderBy('m.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un membre spécifique
     */
    public function findOneMembre(Groupe $groupe, Chauffeur $chauffeur): ?GroupeMembre
    {
        return $this->findOneBy([
            'groupe' => $groupe,
            'chauffeur' => $chauffeur,
        ]);
    }
}
