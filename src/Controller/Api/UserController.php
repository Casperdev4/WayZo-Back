<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        // Convert roles => authority
        $authority = array_map(fn($role) => str_replace('ROLE_', '', $role), $user->getRoles());

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getPrenom() . ' ' . $user->getNom(),
                'email' => $user->getEmail(),
                'authority' => $authority
            ]
        ]);
    }
}







