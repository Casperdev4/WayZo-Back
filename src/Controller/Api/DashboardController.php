<?php

namespace App\Controller\Api;

use App\Repository\ChauffeurRepository;
use App\Repository\RideRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends BaseApiController
{
    public function __construct(
        private RideRepository $rideRepository,
        private ChauffeurRepository $chauffeurRepository
    ) {}

    #[Route('/api/dashboard/chauffeur', name: 'api_dashboard_chauffeur', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function chauffeurDashboard(): JsonResponse
    {
        $user = $this->getChauffeur();
        
        // Récupérer les statistiques du chauffeur connecté
        $myRides = $this->rideRepository->findBy(['chauffeur' => $user]);
        $acceptedRides = $this->rideRepository->findBy(['chauffeurAccepteur' => $user]);
        
        $totalRides = count($myRides) + count($acceptedRides);
        $pendingRides = array_filter($myRides, fn($r) => $r->getStatus() === 'pending');
        $completedRides = array_filter([...$myRides, ...$acceptedRides], fn($r) => $r->getStatus() === 'completed');
        
        // Calcul des revenus (courses vendues)
        $revenue = array_reduce($myRides, function($total, $ride) {
            if ($ride->getStatus() === 'completed') {
                return $total + ($ride->getPrice() ?? 0);
            }
            return $total;
        }, 0);
        
        // Formater les courses récentes
        $recentRides = array_slice($myRides, -10);
        $formattedRides = array_map(function($ride) {
            return [
                'id' => $ride->getId(),
                'clientName' => $ride->getClientName(),
                'depart' => $ride->getDepart(),
                'destination' => $ride->getDestination(),
                'date' => $ride->getDate()?->format('Y-m-d'),
                'time' => $ride->getTime()?->format('H:i'),
                'price' => $ride->getPrice(),
                'status' => $ride->getStatus(),
                'statusVendeur' => $ride->getStatusVendeur(),
            ];
        }, $recentRides);
        
        return new JsonResponse([
            'stats' => [
                'totalRides' => $totalRides,
                'pendingRides' => count($pendingRides),
                'completedRides' => count($completedRides),
                'revenue' => $revenue,
                'rating' => 4.8, // TODO: Calculer depuis les avis
            ],
            'myRides' => $formattedRides,
            'chauffeur' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
            ],
        ]);
    }

    #[Route('/api/dashboard/admin', name: 'api_dashboard_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(): JsonResponse
    {
        // Récupérer toutes les statistiques plateforme
        $allRides = $this->rideRepository->findAll();
        $allChauffeurs = $this->chauffeurRepository->findAll();
        
        $today = new \DateTime('today');
        $todayRides = array_filter($allRides, function($ride) use ($today) {
            return $ride->getDate() && $ride->getDate()->format('Y-m-d') === $today->format('Y-m-d');
        });
        
        $completedRides = array_filter($allRides, fn($r) => $r->getStatus() === 'completed');
        $pendingRides = array_filter($allRides, fn($r) => $r->getStatus() === 'pending');
        
        // Revenus totaux plateforme
        $totalRevenue = array_reduce($allRides, function($total, $ride) {
            if ($ride->getStatus() === 'completed') {
                return $total + ($ride->getPrice() ?? 0);
            }
            return $total;
        }, 0);
        
        // Formater les chauffeurs
        $formattedChauffeurs = array_map(function($chauffeur) {
            return [
                'id' => $chauffeur->getId(),
                'nom' => $chauffeur->getNom(),
                'prenom' => $chauffeur->getPrenom(),
                'email' => $chauffeur->getEmail(),
                'telephone' => $chauffeur->getTel(),
                'rating' => 4.5, // TODO: Calculer depuis les avis
                'ridesCount' => 0, // TODO: Compter les courses
            ];
        }, array_slice($allChauffeurs, 0, 20));
        
        // Formater toutes les courses récentes
        $recentRides = array_slice($allRides, -20);
        $formattedRides = array_map(function($ride) {
            return [
                'id' => $ride->getId(),
                'clientName' => $ride->getClientName(),
                'depart' => $ride->getDepart(),
                'destination' => $ride->getDestination(),
                'date' => $ride->getDate()?->format('Y-m-d'),
                'time' => $ride->getTime()?->format('H:i'),
                'price' => $ride->getPrice(),
                'status' => $ride->getStatus(),
                'statusVendeur' => $ride->getStatusVendeur(),
                'chauffeur' => $ride->getChauffeur() ? [
                    'id' => $ride->getChauffeur()->getId(),
                    'nom' => $ride->getChauffeur()->getNom(),
                    'prenom' => $ride->getChauffeur()->getPrenom(),
                ] : null,
            ];
        }, $recentRides);
        
        return new JsonResponse([
            'stats' => [
                'totalChauffeurs' => count($allChauffeurs),
                'totalRides' => count($allRides),
                'todayRides' => count($todayRides),
                'pendingRides' => count($pendingRides),
                'completedRides' => count($completedRides),
                'totalRevenue' => $totalRevenue,
            ],
            'chauffeurs' => $formattedChauffeurs,
            'rides' => $formattedRides,
        ]);
    }

    #[Route('/api/dashboard/ecommerce', name: 'api_dashboard_ecommerce', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function ecommerceDashboard(): JsonResponse
    {
        // Structure de données compatible ECME
        return new JsonResponse([
            'statisticData' => [
                'totalProfit' => [
                    'thisWeek' => [
                        'value' => 0,
                        'growShrink' => 0,
                        'comparePeriod' => 'from last week',
                        'chartData' => [
                            'series' => [['name' => 'Sales', 'data' => [0, 0, 0, 0, 0, 0, 0]]],
                            'date' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        ],
                    ],
                    'thisMonth' => [
                        'value' => 0,
                        'growShrink' => 0,
                        'comparePeriod' => 'from last month',
                        'chartData' => [
                            'series' => [['name' => 'Sales', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]]],
                            'date' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
                        ],
                    ],
                    'thisYear' => [
                        'value' => 0,
                        'growShrink' => 0,
                        'comparePeriod' => 'from last year',
                        'chartData' => [
                            'series' => [['name' => 'Sales', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]]],
                            'date' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        ],
                    ],
                ],
                'totalOrder' => [
                    'thisWeek' => [
                        'value' => 0,
                        'growShrink' => 0,
                        'comparePeriod' => 'from last week',
                        'chartData' => [
                            'series' => [['name' => 'Orders', 'data' => [0, 0, 0, 0, 0, 0, 0]]],
                            'date' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        ],
                    ],
                    'thisMonth' => [
                        'value' => 0,
                        'growShrink' => 0,
                        'comparePeriod' => 'from last month',
                        'chartData' => [
                            'series' => [['name' => 'Orders', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]]],
                            'date' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
                        ],
                    ],
                    'thisYear' => [
                        'value' => 0,
                        'growShrink' => 0,
                        'comparePeriod' => 'from last year',
                        'chartData' => [
                            'series' => [['name' => 'Orders', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]]],
                            'date' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        ],
                    ],
                ],
                'totalImpression' => [
                    'thisWeek' => [
                        'value' => 0,
                        'growShrink' => 0,
                        'comparePeriod' => 'from last week',
                        'chartData' => [
                            'series' => [['name' => 'Impressions', 'data' => [0, 0, 0, 0, 0, 0, 0]]],
                            'date' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        ],
                    ],
                    'thisMonth' => [
                        'value' => 0,
                        'growShrink' => 0,
                        'comparePeriod' => 'from last month',
                        'chartData' => [
                            'series' => [['name' => 'Impressions', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]]],
                            'date' => ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
                        ],
                    ],
                    'thisYear' => [
                        'value' => 0,
                        'growShrink' => 0,
                        'comparePeriod' => 'from last year',
                        'chartData' => [
                            'series' => [['name' => 'Impressions', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]]],
                            'date' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        ],
                    ],
                ],
            ],
            'salesTarget' => [
                'thisWeek' => ['target' => 100, 'achieved' => 0, 'percentage' => 0],
                'thisMonth' => ['target' => 500, 'achieved' => 0, 'percentage' => 0],
                'thisYear' => ['target' => 5000, 'achieved' => 0, 'percentage' => 0],
            ],
            'recentOrders' => [],
            'topProduct' => [],
            'customerDemographic' => [
                ['id' => 'fr', 'name' => 'France', 'value' => 100, 'coordinates' => [2.3522, 48.8566]],
            ],
            'revenueByChannel' => [
                'thisWeek' => [
                    'value' => 0,
                    'growShrink' => 0,
                    'percentage' => ['onlineStore' => 0, 'physicalStore' => 0, 'socialMedia' => 0],
                ],
                'thisMonth' => [
                    'value' => 0,
                    'growShrink' => 0,
                    'percentage' => ['onlineStore' => 0, 'physicalStore' => 0, 'socialMedia' => 0],
                ],
                'thisYear' => [
                    'value' => 0,
                    'growShrink' => 0,
                    'percentage' => ['onlineStore' => 0, 'physicalStore' => 0, 'socialMedia' => 0],
                ],
            ],
        ]);
    }
}
