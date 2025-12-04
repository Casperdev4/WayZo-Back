<?php

namespace App\Repository;

use App\Entity\Permission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Permission>
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    public function save(Permission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Permission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les permissions par module
     */
    public function findByModule(string $module): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.module = :module')
            ->setParameter('module', $module)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les permissions groupées par module
     */
    public function findAllGroupedByModule(): array
    {
        $permissions = $this->findAll();
        $grouped = [];

        foreach ($permissions as $permission) {
            $module = $permission->getModule();
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    /**
     * Initialise les permissions par défaut si elles n'existent pas
     */
    public function initDefaultPermissions(): void
    {
        $defaultPermissions = [
            [
                'module' => Permission::MODULE_COURSES,
                'name' => 'Gestion des courses',
                'description' => 'Contrôle d\'accès pour la gestion des courses',
                'actions' => [Permission::ACTION_READ, Permission::ACTION_WRITE, Permission::ACTION_DELETE],
            ],
            [
                'module' => Permission::MODULE_CHAUFFEURS,
                'name' => 'Gestion des chauffeurs',
                'description' => 'Contrôle d\'accès pour la gestion des chauffeurs',
                'actions' => [Permission::ACTION_READ, Permission::ACTION_WRITE, Permission::ACTION_DELETE],
            ],
            [
                'module' => Permission::MODULE_MESSAGES,
                'name' => 'Messagerie',
                'description' => 'Contrôle d\'accès pour la messagerie',
                'actions' => [Permission::ACTION_READ, Permission::ACTION_WRITE, Permission::ACTION_DELETE],
            ],
            [
                'module' => Permission::MODULE_TRANSACTIONS,
                'name' => 'Transactions',
                'description' => 'Contrôle d\'accès pour les transactions financières',
                'actions' => [Permission::ACTION_READ, Permission::ACTION_WRITE],
            ],
            [
                'module' => Permission::MODULE_DOCUMENTS,
                'name' => 'Documents',
                'description' => 'Contrôle d\'accès pour la gestion des documents',
                'actions' => [Permission::ACTION_READ, Permission::ACTION_WRITE, Permission::ACTION_DELETE],
            ],
            [
                'module' => Permission::MODULE_SETTINGS,
                'name' => 'Paramètres',
                'description' => 'Contrôle d\'accès pour les paramètres système',
                'actions' => [Permission::ACTION_READ, Permission::ACTION_WRITE],
            ],
            [
                'module' => Permission::MODULE_REPORTS,
                'name' => 'Rapports',
                'description' => 'Contrôle d\'accès pour la génération de rapports',
                'actions' => [Permission::ACTION_READ, Permission::ACTION_WRITE],
            ],
        ];

        foreach ($defaultPermissions as $permData) {
            $existing = $this->findOneBy(['module' => $permData['module']]);
            if (!$existing) {
                $permission = new Permission();
                $permission->setModule($permData['module']);
                $permission->setName($permData['name']);
                $permission->setDescription($permData['description']);
                $permission->setActions($permData['actions']);
                $this->save($permission);
            }
        }

        $this->getEntityManager()->flush();
    }
}
