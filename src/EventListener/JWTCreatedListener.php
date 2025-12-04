<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use App\Entity\Chauffeur;
use App\Service\ActivityLogService;

class JWTCreatedListener
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {}

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof Chauffeur) {
            return;
        }

        // 🔥 Logger la connexion
        $this->activityLogService->logLogin($user);

        // 🔥 ECME attend les rôles en minuscules : 'admin', 'user'
        // Convertir ROLE_ADMIN → admin, ROLE_USER → user
        $roles = array_map(function($role) {
            return strtolower(str_replace('ROLE_', '', $role));
        }, $user->getRoles());

        $data['user'] = [
            'id' => $user->getId(),
            'name' => $user->getPrenom() . ' ' . $user->getNom(),
            'email' => $user->getEmail(),
            'authority' => $roles,  // ['user'] ou ['admin', 'user']
        ];

        $event->setData($data);
    }
}
