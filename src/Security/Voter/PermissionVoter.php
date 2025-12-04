<?php

namespace App\Security\Voter;

use App\Entity\Chauffeur;
use App\Entity\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les permissions granulaires basées sur les modules
 */
class PermissionVoter extends Voter
{
    // Format: MODULE_ACTION (ex: COURSES_READ, CHAUFFEURS_WRITE)
    public const COURSES_READ = 'COURSES_READ';
    public const COURSES_WRITE = 'COURSES_WRITE';
    public const COURSES_DELETE = 'COURSES_DELETE';
    
    public const CHAUFFEURS_READ = 'CHAUFFEURS_READ';
    public const CHAUFFEURS_WRITE = 'CHAUFFEURS_WRITE';
    public const CHAUFFEURS_DELETE = 'CHAUFFEURS_DELETE';
    
    public const MESSAGES_READ = 'MESSAGES_READ';
    public const MESSAGES_WRITE = 'MESSAGES_WRITE';
    public const MESSAGES_DELETE = 'MESSAGES_DELETE';
    
    public const TRANSACTIONS_READ = 'TRANSACTIONS_READ';
    public const TRANSACTIONS_WRITE = 'TRANSACTIONS_WRITE';
    
    public const DOCUMENTS_READ = 'DOCUMENTS_READ';
    public const DOCUMENTS_WRITE = 'DOCUMENTS_WRITE';
    public const DOCUMENTS_DELETE = 'DOCUMENTS_DELETE';
    
    public const SETTINGS_READ = 'SETTINGS_READ';
    public const SETTINGS_WRITE = 'SETTINGS_WRITE';
    
    public const REPORTS_READ = 'REPORTS_READ';
    public const REPORTS_WRITE = 'REPORTS_WRITE';

    private const SUPPORTED_ATTRIBUTES = [
        self::COURSES_READ, self::COURSES_WRITE, self::COURSES_DELETE,
        self::CHAUFFEURS_READ, self::CHAUFFEURS_WRITE, self::CHAUFFEURS_DELETE,
        self::MESSAGES_READ, self::MESSAGES_WRITE, self::MESSAGES_DELETE,
        self::TRANSACTIONS_READ, self::TRANSACTIONS_WRITE,
        self::DOCUMENTS_READ, self::DOCUMENTS_WRITE, self::DOCUMENTS_DELETE,
        self::SETTINGS_READ, self::SETTINGS_WRITE,
        self::REPORTS_READ, self::REPORTS_WRITE,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::SUPPORTED_ATTRIBUTES, true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof Chauffeur) {
            return false;
        }

        // Parse l'attribut pour extraire le module et l'action
        $parts = explode('_', $attribute);
        if (count($parts) !== 2) {
            return false;
        }

        $module = strtolower($parts[0]);
        $action = strtolower($parts[1]);

        // Mapper vers les constantes de Permission
        $moduleMap = [
            'courses' => Permission::MODULE_COURSES,
            'chauffeurs' => Permission::MODULE_CHAUFFEURS,
            'messages' => Permission::MODULE_MESSAGES,
            'transactions' => Permission::MODULE_TRANSACTIONS,
            'documents' => Permission::MODULE_DOCUMENTS,
            'settings' => Permission::MODULE_SETTINGS,
            'reports' => Permission::MODULE_REPORTS,
        ];

        $permissionModule = $moduleMap[$module] ?? null;
        if (!$permissionModule) {
            return false;
        }

        // Super admin a tous les droits
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Admin a la plupart des droits (sauf suppression chauffeurs)
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            // Admin ne peut pas supprimer les chauffeurs
            if ($permissionModule === Permission::MODULE_CHAUFFEURS && $action === 'delete') {
                return false;
            }
            return true;
        }

        // Vérifier via les rôles personnalisés
        return $user->hasAccessTo($permissionModule, $action);
    }
}
