<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    #[Route('/api/debug-login', name: 'api_debug_login', methods: ['POST'])]
    public function debugLogin(Request $request): JsonResponse
    {
        // Voir EXACTEMENT ce que symfony reçoit
        $data = $request->toArray();

        return new JsonResponse([
            'received' => $data
        ]);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'message' => 'API login route active'
        ]);
    }
}





