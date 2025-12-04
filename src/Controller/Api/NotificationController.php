<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/api/notification/count', name: 'api_notification_count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        // 🔥 TODO : Compter les vraies notifs
        return $this->json([
            'count' => 0
        ]);
    }
}








