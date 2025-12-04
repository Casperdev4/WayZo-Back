<?php

namespace App\Controller\Api;

use App\Entity\Chauffeur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Contrôleur de base pour l'API
 * Fournit des méthodes utilitaires communes
 */
abstract class BaseApiController extends AbstractController
{
    /**
     * Récupère l'utilisateur connecté typé comme Chauffeur
     * 
     * @throws AccessDeniedException si l'utilisateur n'est pas connecté ou n'est pas un Chauffeur
     */
    protected function getChauffeur(): Chauffeur
    {
        $user = $this->getUser();
        
        if (!$user instanceof Chauffeur) {
            throw new AccessDeniedException('Utilisateur non authentifié ou type invalide');
        }
        
        return $user;
    }
}
