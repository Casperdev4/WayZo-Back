<?php

namespace App\Controller\Api;

use App\Service\ExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/exports')]
class ExportController extends AbstractController
{
    public function __construct(
        private ExportService $exportService
    ) {
    }

    /**
     * Récupère les données de transactions pour preview
     */
    #[Route('/transactions', name: 'api_exports_transactions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getTransactions(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }
        
        $filters = $this->extractFilters($request);
        
        try {
            $data = $this->exportService->getTransactionsData($user, $filters);
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la récupération des transactions',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export CSV des transactions
     */
    #[Route('/transactions/csv', name: 'api_exports_transactions_csv', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function exportCSV(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }
        
        $filters = $this->extractFilters($request);
        
        try {
            return $this->exportService->generateCSV($user, $filters);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la génération du CSV',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export PDF des transactions
     */
    #[Route('/transactions/pdf', name: 'api_exports_transactions_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function exportPDF(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }
        
        $filters = $this->extractFilters($request);
        
        try {
            return $this->exportService->generatePDF($user, $filters);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la génération du PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aperçu HTML pour visualisation
     */
    #[Route('/transactions/preview', name: 'api_exports_transactions_preview', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function previewTransactions(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }
        
        $filters = $this->extractFilters($request);
        
        try {
            $html = $this->exportService->generatePreview($user, $filters);
            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=utf-8'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la génération de l\'aperçu',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques rapides des transactions
     */
    #[Route('/transactions/stats', name: 'api_exports_transactions_stats', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getStats(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non connecté'], 401);
        }
        
        $filters = $this->extractFilters($request);
        
        try {
            $data = $this->exportService->getTransactionsData($user, $filters);
            return $this->json([
                'stats' => $data['stats'],
                'filters' => $data['filters'],
                'generatedAt' => $data['generatedAt']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors du calcul des statistiques',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extrait les filtres de la requête
     */
    private function extractFilters(Request $request): array
    {
        $filters = [];
        
        // Dates
        if ($dateFrom = $request->query->get('dateFrom')) {
            try {
                $filters['dateFrom'] = new \DateTime($dateFrom);
            } catch (\Exception $e) {
                // Ignorer les dates invalides
            }
        }
        
        if ($dateTo = $request->query->get('dateTo')) {
            try {
                $filters['dateTo'] = new \DateTime($dateTo);
            } catch (\Exception $e) {
                // Ignorer les dates invalides
            }
        }
        
        // Statut
        $statut = $request->query->get('statut');
        if ($statut && in_array($statut, ['pending', 'completed', 'cancelled', 'refunded'])) {
            $filters['statut'] = $statut;
        }
        
        // Type (sent/received)
        $type = $request->query->get('type');
        if ($type && in_array($type, ['sent', 'received'])) {
            $filters['type'] = $type;
        }
        
        return $filters;
    }
}
