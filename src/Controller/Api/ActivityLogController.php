<?php

namespace App\Controller\Api;

use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ActivityLogController extends AbstractController
{
    public function __construct(
        private ActivityLogRepository $activityLogRepository
    ) {}

    /**
     * Récupérer les logs de l'utilisateur connecté
     */
    #[Route('/logs', name: 'api_logs_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getLogs(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $page = $request->query->getInt('activityIndex', 1);
        $type = $request->query->get('type');
        
        // Récupérer les logs groupés par date
        $groupedLogs = $this->activityLogRepository->findGroupedByDate($user, $page);
        
        // Formater pour le composant ECME Activity
        $data = array_map(function($group) {
            return [
                'id' => 'log-' . $group['date'],
                'date' => $group['date'],
                'events' => array_map(function($log) {
                    return $this->formatLogEvent($log);
                }, $group['events']),
            ];
        }, $groupedLogs);
        
        // Vérifier s'il y a plus de logs
        $totalLogs = $this->activityLogRepository->countByUser($user);
        $loadable = ($page * 50) < $totalLogs;
        
        return new JsonResponse([
            'data' => $data,
            'loadable' => $loadable,
        ]);
    }

    /**
     * Récupérer tous les logs (admin seulement)
     */
    #[Route('/logs/all', name: 'api_logs_all', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllLogs(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $type = $request->query->get('type');
        
        $logs = $this->activityLogRepository->findAllPaginated($page, 50, $type);
        
        $data = array_map(function($log) {
            $formatted = $this->formatLogEvent($log);
            $formatted['user'] = [
                'id' => $log->getChauffeur()->getId(),
                'name' => $log->getChauffeur()->getPrenom() . ' ' . $log->getChauffeur()->getNom(),
            ];
            return $formatted;
        }, $logs);
        
        return new JsonResponse([
            'data' => $data,
            'page' => $page,
        ]);
    }

    /**
     * Récupérer les dernières connexions
     */
    #[Route('/logs/logins', name: 'api_logs_logins', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getLastLogins(): JsonResponse
    {
        $user = $this->getUser();
        $logins = $this->activityLogRepository->findLastLogins($user, 10);
        
        $data = array_map(function($log) {
            return [
                'id' => $log->getId(),
                'date' => $log->getCreatedAt()->format('c'),
                'ipAddress' => $log->getIpAddress(),
                'userAgent' => $this->parseUserAgent($log->getUserAgent()),
                'location' => 'France', // TODO: Géolocaliser l'IP
            ];
        }, $logins);
        
        return new JsonResponse($data);
    }

    /**
     * Statistiques des activités
     */
    #[Route('/logs/stats', name: 'api_logs_stats', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();
        
        // TODO: Implémenter les stats réelles
        return new JsonResponse([
            'totalLogins' => 45,
            'ridesCreated' => 12,
            'ridesAccepted' => 8,
            'ridesCompleted' => 15,
            'messagesSent' => 32,
            'lastLogin' => (new \DateTime())->format('c'),
        ]);
    }

    /**
     * Formater un log pour le frontend
     */
    private function formatLogEvent(ActivityLog $log): array
    {
        $typeMapping = [
            ActivityLog::TYPE_LOGIN => 'LOGIN',
            ActivityLog::TYPE_LOGOUT => 'LOGOUT',
            ActivityLog::TYPE_RIDE_CREATED => 'CREATE_TICKET',
            ActivityLog::TYPE_RIDE_ACCEPTED => 'UPDATE_TICKET',
            ActivityLog::TYPE_RIDE_COMPLETED => 'UPDATE_TICKET',
            ActivityLog::TYPE_RIDE_CANCELLED => 'UPDATE_TICKET',
            ActivityLog::TYPE_MESSAGE_SENT => 'COMMENT',
            ActivityLog::TYPE_PROFILE_UPDATED => 'UPDATE_TICKET',
            ActivityLog::TYPE_PASSWORD_CHANGED => 'UPDATE_TICKET',
            ActivityLog::TYPE_DOCUMENT_UPLOADED => 'ADD_FILES_TO_TICKET',
        ];

        $iconMapping = [
            ActivityLog::TYPE_LOGIN => '🔐',
            ActivityLog::TYPE_LOGOUT => '🚪',
            ActivityLog::TYPE_RIDE_CREATED => '🚗',
            ActivityLog::TYPE_RIDE_ACCEPTED => '✅',
            ActivityLog::TYPE_RIDE_COMPLETED => '🏁',
            ActivityLog::TYPE_RIDE_CANCELLED => '❌',
            ActivityLog::TYPE_MESSAGE_SENT => '💬',
            ActivityLog::TYPE_PROFILE_UPDATED => '👤',
            ActivityLog::TYPE_PASSWORD_CHANGED => '🔑',
            ActivityLog::TYPE_DOCUMENT_UPLOADED => '📄',
        ];

        return [
            'id' => $log->getId(),
            'type' => $typeMapping[$log->getType()] ?? 'UPDATE_TICKET',
            'activityType' => $log->getType(),
            'dateTime' => $log->getCreatedAt()->getTimestamp(),
            'ticket' => $log->getDescription(),
            'userName' => $log->getChauffeur()->getPrenom() . ' ' . $log->getChauffeur()->getNom(),
            'userImg' => '/img/avatars/thumb-' . ($log->getChauffeur()->getId() % 15 + 1) . '.jpg',
            'comment' => $log->getDescription(),
            'tags' => [],
            'files' => [],
            'assignees' => [],
            'icon' => $iconMapping[$log->getType()] ?? '📋',
            'metadata' => $log->getMetadata(),
            'ipAddress' => $log->getIpAddress(),
        ];
    }

    /**
     * Parser le User-Agent pour un affichage lisible
     */
    private function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return ['browser' => 'Inconnu', 'os' => 'Inconnu', 'device' => 'Inconnu'];
        }

        $browser = 'Autre';
        $os = 'Autre';
        $device = 'Desktop';

        // Détection navigateur
        if (str_contains($userAgent, 'Chrome')) $browser = 'Chrome';
        elseif (str_contains($userAgent, 'Firefox')) $browser = 'Firefox';
        elseif (str_contains($userAgent, 'Safari')) $browser = 'Safari';
        elseif (str_contains($userAgent, 'Edge')) $browser = 'Edge';

        // Détection OS
        if (str_contains($userAgent, 'Windows')) $os = 'Windows';
        elseif (str_contains($userAgent, 'Mac')) $os = 'macOS';
        elseif (str_contains($userAgent, 'Linux')) $os = 'Linux';
        elseif (str_contains($userAgent, 'Android')) { $os = 'Android'; $device = 'Mobile'; }
        elseif (str_contains($userAgent, 'iPhone')) { $os = 'iOS'; $device = 'Mobile'; }

        return ['browser' => $browser, 'os' => $os, 'device' => $device];
    }
}
