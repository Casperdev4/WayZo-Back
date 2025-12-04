<?php

namespace App\Repository;

use App\Entity\Role;
use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function save(Role $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Role $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve un rôle par son code
     */
    public function findByCode(string $code): ?Role
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Trouve tous les rôles non-système (personnalisables)
     */
    public function findCustomRoles(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isSystem = :isSystem')
            ->setParameter('isSystem', false)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les rôles système
     */
    public function findSystemRoles(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isSystem = :isSystem')
            ->setParameter('isSystem', true)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Initialise les rôles par défaut si ils n'existent pas
     */
    public function initDefaultRoles(): void
    {
        $defaultRoles = [
            [
                'code' => Role::ROLE_SUPER_ADMIN,
                'name' => 'Super Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'isSystem' => true,
                'accessRights' => [
                    Permission::MODULE_COURSES => [Permission::ACTION_READ, Permission::ACTION_WRITE, Permission::ACTION_DELETE],
                    Permission::MODULE_CHAUFFEURS => [Permission::ACTION_READ, Permission::ACTION_WRITE, Permission::ACTION_DELETE],
                    Permission::MODULE_MESSAGES => [Permission::ACTION_READ, Permission::ACTION_WRITE, Permission::ACTION_DELETE],
                    Permission::MODULE_TRANSACTIONS => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                    Permission::MODULE_DOCUMENTS => [Permission::ACTION_READ, Permission::ACTION_WRITE, Permission::ACTION_DELETE],
                    Permission::MODULE_SETTINGS => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                    Permission::MODULE_REPORTS => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                ],
            ],
            [
                'code' => Role::ROLE_ADMIN,
                'name' => 'Administrateur',
                'description' => 'Gestion des chauffeurs et des courses',
                'isSystem' => true,
                'accessRights' => [
                    Permission::MODULE_COURSES => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                    Permission::MODULE_CHAUFFEURS => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                    Permission::MODULE_MESSAGES => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                    Permission::MODULE_TRANSACTIONS => [Permission::ACTION_READ],
                    Permission::MODULE_DOCUMENTS => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                    Permission::MODULE_SETTINGS => [Permission::ACTION_READ],
                    Permission::MODULE_REPORTS => [Permission::ACTION_READ],
                ],
            ],
            [
                'code' => Role::ROLE_CHAUFFEUR,
                'name' => 'Chauffeur',
                'description' => 'Accès standard pour les chauffeurs VTC',
                'isSystem' => true,
                'accessRights' => [
                    Permission::MODULE_COURSES => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                    Permission::MODULE_CHAUFFEURS => [Permission::ACTION_READ],
                    Permission::MODULE_MESSAGES => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                    Permission::MODULE_TRANSACTIONS => [Permission::ACTION_READ],
                    Permission::MODULE_DOCUMENTS => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                    Permission::MODULE_SETTINGS => [Permission::ACTION_READ, Permission::ACTION_WRITE],
                    Permission::MODULE_REPORTS => [Permission::ACTION_READ],
                ],
            ],
        ];

        foreach ($defaultRoles as $roleData) {
            $existing = $this->findByCode($roleData['code']);
            if (!$existing) {
                $role = new Role();
                $role->setCode($roleData['code']);
                $role->setName($roleData['name']);
                $role->setDescription($roleData['description']);
                $role->setIsSystem($roleData['isSystem']);
                $role->setAccessRights($roleData['accessRights']);
                $this->save($role);
            }
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Compte les utilisateurs par rôle
     */
    public function countUsersByRole(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.id, r.name, COUNT(u.id) as userCount')
            ->leftJoin('r.users', 'u')
            ->groupBy('r.id')
            ->orderBy('userCount', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
