<?php

namespace App\Controller\Api;

use App\Entity\Role;
use App\Entity\Permission;
use App\Entity\Chauffeur;
use App\Repository\RoleRepository;
use App\Repository\PermissionRepository;
use App\Repository\ChauffeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/rbac')]
class RbacController extends AbstractController
{
    public function __construct(
        private RoleRepository $roleRepository,
        private PermissionRepository $permissionRepository,
        private ChauffeurRepository $chauffeurRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Récupère tous les rôles avec leurs utilisateurs
     */
    #[Route('/roles', name: 'api_rbac_roles', methods: ['GET'])]
    public function getRoles(): JsonResponse
    {
        // Initialiser les rôles par défaut si nécessaire
        $this->roleRepository->initDefaultRoles();
        
        $roles = $this->roleRepository->findAll();
        
        $result = [];
        foreach ($roles as $role) {
            $result[] = $role->toArray();
        }

        return $this->json($result);
    }

    /**
     * Crée un nouveau rôle
     */
    #[Route('/roles', name: 'api_rbac_roles_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createRole(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom du rôle est requis'], 400);
        }

        // Générer un code unique
        $code = 'ROLE_' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '_', $data['name']));
        
        // Vérifier si le code existe déjà
        if ($this->roleRepository->findByCode($code)) {
            $code .= '_' . uniqid();
        }

        $role = new Role();
        $role->setCode($code);
        $role->setName($data['name']);
        $role->setDescription($data['description'] ?? '');
        $role->setIsSystem(false);
        $role->setAccessRights($data['accessRight'] ?? []);

        $this->roleRepository->save($role, true);

        return $this->json($role->toArray(), 201);
    }

    /**
     * Met à jour un rôle
     */
    #[Route('/roles/{id}', name: 'api_rbac_roles_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateRole(int $id, Request $request): JsonResponse
    {
        $role = $this->roleRepository->find($id);
        
        if (!$role) {
            return $this->json(['error' => 'Rôle non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $role->setName($data['name']);
        }
        
        if (isset($data['description'])) {
            $role->setDescription($data['description']);
        }
        
        if (isset($data['accessRight'])) {
            $role->setAccessRights($data['accessRight']);
        }

        $role->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->json($role->toArray());
    }

    /**
     * Supprime un rôle (sauf rôles système)
     */
    #[Route('/roles/{id}', name: 'api_rbac_roles_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteRole(int $id): JsonResponse
    {
        $role = $this->roleRepository->find($id);
        
        if (!$role) {
            return $this->json(['error' => 'Rôle non trouvé'], 404);
        }

        if ($role->isSystem()) {
            return $this->json(['error' => 'Impossible de supprimer un rôle système'], 403);
        }

        $this->roleRepository->remove($role, true);

        return $this->json(['message' => 'Rôle supprimé avec succès']);
    }

    /**
     * Récupère toutes les permissions disponibles
     */
    #[Route('/permissions', name: 'api_rbac_permissions', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getPermissions(): JsonResponse
    {
        // Initialiser les permissions par défaut
        $this->permissionRepository->initDefaultPermissions();
        
        $permissions = $this->permissionRepository->findAll();
        
        $result = [];
        foreach ($permissions as $permission) {
            $result[] = $permission->toArray();
        }

        return $this->json($result);
    }

    /**
     * Récupère les modules d'accès pour le frontend
     */
    #[Route('/access-modules', name: 'api_rbac_access_modules', methods: ['GET'])]
    public function getAccessModules(): JsonResponse
    {
        $modules = [
            [
                'id' => Permission::MODULE_COURSES,
                'name' => 'Gestion des courses',
                'description' => 'Contrôle d\'accès pour la gestion des courses',
                'accessor' => [
                    ['label' => 'Lecture', 'value' => 'read'],
                    ['label' => 'Écriture', 'value' => 'write'],
                    ['label' => 'Suppression', 'value' => 'delete'],
                ],
            ],
            [
                'id' => Permission::MODULE_CHAUFFEURS,
                'name' => 'Gestion des chauffeurs',
                'description' => 'Contrôle d\'accès pour la gestion des chauffeurs',
                'accessor' => [
                    ['label' => 'Lecture', 'value' => 'read'],
                    ['label' => 'Écriture', 'value' => 'write'],
                    ['label' => 'Suppression', 'value' => 'delete'],
                ],
            ],
            [
                'id' => Permission::MODULE_MESSAGES,
                'name' => 'Messagerie',
                'description' => 'Contrôle d\'accès pour la messagerie',
                'accessor' => [
                    ['label' => 'Lecture', 'value' => 'read'],
                    ['label' => 'Écriture', 'value' => 'write'],
                    ['label' => 'Suppression', 'value' => 'delete'],
                ],
            ],
            [
                'id' => Permission::MODULE_TRANSACTIONS,
                'name' => 'Transactions',
                'description' => 'Contrôle d\'accès pour les transactions financières',
                'accessor' => [
                    ['label' => 'Lecture', 'value' => 'read'],
                    ['label' => 'Écriture', 'value' => 'write'],
                ],
            ],
            [
                'id' => Permission::MODULE_DOCUMENTS,
                'name' => 'Documents',
                'description' => 'Contrôle d\'accès pour la gestion des documents',
                'accessor' => [
                    ['label' => 'Lecture', 'value' => 'read'],
                    ['label' => 'Écriture', 'value' => 'write'],
                    ['label' => 'Suppression', 'value' => 'delete'],
                ],
            ],
            [
                'id' => Permission::MODULE_SETTINGS,
                'name' => 'Paramètres',
                'description' => 'Contrôle d\'accès pour les paramètres système',
                'accessor' => [
                    ['label' => 'Lecture', 'value' => 'read'],
                    ['label' => 'Écriture', 'value' => 'write'],
                ],
            ],
            [
                'id' => Permission::MODULE_REPORTS,
                'name' => 'Rapports',
                'description' => 'Contrôle d\'accès pour la génération de rapports',
                'accessor' => [
                    ['label' => 'Lecture', 'value' => 'read'],
                    ['label' => 'Écriture', 'value' => 'write'],
                ],
            ],
        ];

        return $this->json($modules);
    }

    /**
     * Récupère la liste des utilisateurs avec pagination et filtres
     */
    #[Route('/users', name: 'api_rbac_users', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getUsers(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('pageIndex', 1));
        $limit = min(100, max(1, (int) $request->query->get('pageSize', 10)));
        $query = $request->query->get('query', '');
        $status = $request->query->get('status', '');
        $role = $request->query->get('role', '');
        $sortKey = $request->query->get('sort', '');
        $sortOrder = $request->query->get('order', 'ASC');

        $qb = $this->chauffeurRepository->createQueryBuilder('c')
            ->leftJoin('c.customRoles', 'r');

        // Filtre par recherche
        if ($query) {
            $qb->andWhere('c.nom LIKE :query OR c.prenom LIKE :query OR c.email LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        // Filtre par statut
        if ($status) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        // Filtre par rôle
        if ($role) {
            $qb->andWhere('r.id = :roleId')
               ->setParameter('roleId', $role);
        }

        // Comptage total
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(DISTINCT c.id)')->getQuery()->getSingleScalarResult();

        // Tri
        if ($sortKey) {
            $qb->orderBy('c.' . $sortKey, $sortOrder);
        } else {
            $qb->orderBy('c.id', 'DESC');
        }

        // Pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $users = $qb->getQuery()->getResult();

        $result = [];
        foreach ($users as $user) {
            $primaryRole = $user->getPrimaryRole();
            $roleId = null;
            
            // Déterminer le rôle principal
            if ($primaryRole) {
                $roleId = (string) $primaryRole->getId();
            } else {
                // Chercher le rôle système correspondant
                $systemRoles = $user->getRoles();
                if (in_array('ROLE_ADMIN', $systemRoles)) {
                    $adminRole = $this->roleRepository->findByCode(Role::ROLE_ADMIN);
                    $roleId = $adminRole ? (string) $adminRole->getId() : null;
                } else {
                    $chauffeurRole = $this->roleRepository->findByCode(Role::ROLE_CHAUFFEUR);
                    $roleId = $chauffeurRole ? (string) $chauffeurRole->getId() : null;
                }
            }

            $result[] = [
                'id' => (string) $user->getId(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail(),
                'img' => '/img/avatars/' . ($user->getId() % 10 + 1) . '.jpg',
                'status' => $user->getStatus(),
                'role' => $roleId,
                'lastOnline' => $user->getLastOnline()?->getTimestamp() ?? time(),
                'phone' => $user->getTel(),
                'company' => $user->getNomSociete(),
            ];
        }

        return $this->json([
            'list' => $result,
            'total' => $total,
        ]);
    }

    /**
     * Met à jour le rôle d'un utilisateur
     */
    #[Route('/users/{id}/role', name: 'api_rbac_user_role', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateUserRole(int $id, Request $request): JsonResponse
    {
        $user = $this->chauffeurRepository->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $roleId = $data['role'] ?? null;

        if (!$roleId) {
            return $this->json(['error' => 'ID du rôle requis'], 400);
        }

        $role = $this->roleRepository->find($roleId);
        
        if (!$role) {
            return $this->json(['error' => 'Rôle non trouvé'], 404);
        }

        // Supprimer les anciens rôles personnalisés
        foreach ($user->getCustomRoles() as $oldRole) {
            $user->removeCustomRole($oldRole);
        }

        // Ajouter le nouveau rôle
        $user->addCustomRole($role);

        // Mettre à jour les rôles Symfony si c'est un rôle système
        if ($role->getCode() === Role::ROLE_ADMIN) {
            $user->setRoles(['ROLE_ADMIN']);
        } elseif ($role->getCode() === Role::ROLE_SUPER_ADMIN) {
            $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']);
        } else {
            $user->setRoles(['ROLE_USER']);
        }

        $this->em->flush();

        return $this->json(['message' => 'Rôle mis à jour avec succès']);
    }

    /**
     * Met à jour le statut d'un utilisateur (actif/bloqué)
     */
    #[Route('/users/{id}/status', name: 'api_rbac_user_status', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateUserStatus(int $id, Request $request): JsonResponse
    {
        $user = $this->chauffeurRepository->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        if (!in_array($status, [Chauffeur::STATUS_ACTIVE, Chauffeur::STATUS_BLOCKED, Chauffeur::STATUS_PENDING])) {
            return $this->json(['error' => 'Statut invalide'], 400);
        }

        $user->setStatus($status);
        $this->em->flush();

        return $this->json(['message' => 'Statut mis à jour avec succès']);
    }

    /**
     * Supprime plusieurs utilisateurs
     */
    #[Route('/users/bulk-delete', name: 'api_rbac_users_bulk_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function bulkDeleteUsers(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return $this->json(['error' => 'Aucun utilisateur sélectionné'], 400);
        }

        $deleted = 0;
        foreach ($ids as $id) {
            $user = $this->chauffeurRepository->find($id);
            if ($user) {
                // Ne pas supprimer les admins
                if (!in_array('ROLE_ADMIN', $user->getRoles())) {
                    $this->em->remove($user);
                    $deleted++;
                }
            }
        }

        $this->em->flush();

        return $this->json([
            'message' => "$deleted utilisateur(s) supprimé(s) avec succès",
            'deleted' => $deleted,
        ]);
    }

    /**
     * Vérifie si l'utilisateur courant a une permission
     */
    #[Route('/check-permission', name: 'api_rbac_check_permission', methods: ['POST'])]
    public function checkPermission(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof Chauffeur) {
            return $this->json(['hasAccess' => false]);
        }

        $data = json_decode($request->getContent(), true);
        $module = $data['module'] ?? null;
        $action = $data['action'] ?? null;

        if (!$module || !$action) {
            return $this->json(['error' => 'Module et action requis'], 400);
        }

        $hasAccess = $user->hasAccessTo($module, $action);

        return $this->json(['hasAccess' => $hasAccess]);
    }

    /**
     * Récupère les statistiques des rôles
     */
    #[Route('/stats', name: 'api_rbac_stats', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getStats(): JsonResponse
    {
        $totalUsers = $this->chauffeurRepository->count([]);
        $activeUsers = $this->chauffeurRepository->count(['status' => Chauffeur::STATUS_ACTIVE]);
        $blockedUsers = $this->chauffeurRepository->count(['status' => Chauffeur::STATUS_BLOCKED]);
        $totalRoles = $this->roleRepository->count([]);
        $customRoles = $this->roleRepository->count(['isSystem' => false]);

        return $this->json([
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'blockedUsers' => $blockedUsers,
            'totalRoles' => $totalRoles,
            'customRoles' => $customRoles,
        ]);
    }
}
