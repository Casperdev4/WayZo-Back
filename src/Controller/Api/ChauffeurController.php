<?php

namespace App\Controller\Api;

use App\Entity\Chauffeur;
use App\Repository\AvisRepository;
use App\Repository\ChauffeurRepository;
use App\Repository\RideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/chauffeurs')]
class ChauffeurController extends BaseApiController
{
    public function __construct(
        private ChauffeurRepository $chauffeurRepository,
        private RideRepository $rideRepository,
        private AvisRepository $avisRepository,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * Liste de tous les chauffeurs
     */
    #[Route('', name: 'api_chauffeurs_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('perPage', 10);
        $search = $request->query->get('search', '');
        
        // Filtrage basique
        $chauffeurs = $this->chauffeurRepository->findAll();
        
        // Recherche
        if ($search) {
            $chauffeurs = array_filter($chauffeurs, function($c) use ($search) {
                $searchLower = strtolower($search);
                return str_contains(strtolower($c->getNom()), $searchLower)
                    || str_contains(strtolower($c->getPrenom()), $searchLower)
                    || str_contains(strtolower($c->getEmail()), $searchLower)
                    || str_contains(strtolower($c->getNomSociete() ?? ''), $searchLower);
            });
        }
        
        $total = count($chauffeurs);
        
        // Pagination
        $offset = ($page - 1) * $perPage;
        $chauffeurs = array_slice($chauffeurs, $offset, $perPage);
        
        $data = array_map(function($chauffeur) {
            $ridesCount = count($this->rideRepository->findBy(['chauffeur' => $chauffeur]));
            $avgRating = $this->avisRepository->getAverageRating($chauffeur);
            $avisCount = $this->avisRepository->countAvisForChauffeur($chauffeur);
            
            return [
                'id' => $chauffeur->getId(),
                'name' => $chauffeur->getPrenom() . ' ' . $chauffeur->getNom(),
                'firstName' => $chauffeur->getPrenom(),
                'lastName' => $chauffeur->getNom(),
                'email' => $chauffeur->getEmail(),
                'phone' => $chauffeur->getTel(),
                'company' => $chauffeur->getNomSociete(),
                'siret' => $chauffeur->getSiret(),
                'vehicle' => $chauffeur->getVehicle(),
                'img' => '/img/avatars/thumb-' . ($chauffeur->getId() % 15 + 1) . '.jpg',
                'status' => $chauffeur->getStatus(),
                'rating' => $avgRating ?? 0,
                'avisCount' => $avisCount,
                'ridesCount' => $ridesCount,
                'totalSpent' => 0, // TODO: Calculer
                'createdAt' => '2024-01-01', // TODO: Ajouter champ createdAt
            ];
        }, $chauffeurs);
        
        return new JsonResponse([
            'list' => array_values($data),
            'total' => $total,
        ]);
    }

    /**
     * Détails d'un chauffeur
     */
    #[Route('/{id}', name: 'api_chauffeur_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(int $id): JsonResponse
    {
        $chauffeur = $this->chauffeurRepository->find($id);
        
        if (!$chauffeur) {
            return new JsonResponse(['error' => 'Chauffeur non trouvé'], 404);
        }
        
        // Récupérer les courses
        $rides = $this->rideRepository->findBy(['chauffeur' => $chauffeur], ['date' => 'DESC'], 10);
        
        // Récupérer les stats d'avis
        $avgRating = $this->avisRepository->getAverageRating($chauffeur);
        $avisCount = $this->avisRepository->countAvisForChauffeur($chauffeur);
        $ratingDistribution = $this->avisRepository->getRatingDistribution($chauffeur);
        
        return new JsonResponse([
            'id' => $chauffeur->getId(),
            'name' => $chauffeur->getPrenom() . ' ' . $chauffeur->getNom(),
            'firstName' => $chauffeur->getPrenom(),
            'lastName' => $chauffeur->getNom(),
            'email' => $chauffeur->getEmail(),
            'phone' => $chauffeur->getTel(),
            'company' => $chauffeur->getNomSociete(),
            'siret' => $chauffeur->getSiret(),
            'kbis' => $chauffeur->getKbis(),
            'carteVtc' => $chauffeur->getCarteVtc(),
            'permis' => $chauffeur->getPermis(),
            'vehicle' => $chauffeur->getVehicle(),
            'dateNaissance' => $chauffeur->getDateNaissance()?->format('Y-m-d'),
            'img' => '/img/avatars/thumb-' . ($chauffeur->getId() % 15 + 1) . '.jpg',
            'status' => $chauffeur->getStatus(),
            'rating' => $avgRating ?? 0,
            'avisCount' => $avisCount,
            'ratingDistribution' => $ratingDistribution,
            'recentRides' => array_map(function($ride) {
                return [
                    'id' => $ride->getId(),
                    'clientName' => $ride->getClientName(),
                    'depart' => $ride->getDepart(),
                    'destination' => $ride->getDestination(),
                    'date' => $ride->getDate()?->format('Y-m-d'),
                    'price' => $ride->getPrice(),
                    'status' => $ride->getStatus(),
                ];
            }, $rides),
            'personalInfo' => [
                'location' => 'France',
                'title' => 'Chauffeur VTC',
                'birthday' => $chauffeur->getDateNaissance()?->format('d/m/Y'),
            ],
        ]);
    }

    /**
     * Créer un nouveau chauffeur (Admin only)
     */
    #[Route('', name: 'api_chauffeur_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Validation basique
        $required = ['nom', 'prenom', 'email', 'tel', 'siret', 'nomSociete', 'kbis', 'carteVtc', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new JsonResponse(['error' => "Le champ $field est requis"], 400);
            }
        }
        
        // Vérifier que l'email n'existe pas déjà
        if ($this->chauffeurRepository->findOneBy(['email' => $data['email']])) {
            return new JsonResponse(['error' => 'Cet email est déjà utilisé'], 400);
        }
        
        $chauffeur = new Chauffeur();
        $chauffeur->setNom($data['nom']);
        $chauffeur->setPrenom($data['prenom']);
        $chauffeur->setEmail($data['email']);
        $chauffeur->setTel($data['tel']);
        $chauffeur->setSiret($data['siret']);
        $chauffeur->setNomSociete($data['nomSociete']);
        $chauffeur->setKbis($data['kbis']);
        $chauffeur->setCarteVtc($data['carteVtc']);
        $chauffeur->setPermis($data['permis'] ?? null);
        $chauffeur->setVehicle($data['vehicle'] ?? null);
        $chauffeur->setRoles(['ROLE_USER']);
        
        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($chauffeur, $data['password']);
        $chauffeur->setPassword($hashedPassword);
        
        if (!empty($data['dateNaissance'])) {
            $chauffeur->setDateNaissance(new \DateTimeImmutable($data['dateNaissance']));
        }
        
        $this->em->persist($chauffeur);
        $this->em->flush();
        
        return new JsonResponse([
            'id' => $chauffeur->getId(),
            'message' => 'Chauffeur créé avec succès',
        ], 201);
    }

    /**
     * Modifier un chauffeur
     */
    #[Route('/{id}', name: 'api_chauffeur_update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $chauffeur = $this->chauffeurRepository->find($id);
        
        if (!$chauffeur) {
            return new JsonResponse(['error' => 'Chauffeur non trouvé'], 404);
        }
        
        // Vérifier les permissions (admin ou propriétaire)
        $user = $this->getChauffeur();
        if (!$this->isGranted('ROLE_ADMIN') && $user->getId() !== $chauffeur->getId()) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['nom'])) $chauffeur->setNom($data['nom']);
        if (isset($data['prenom'])) $chauffeur->setPrenom($data['prenom']);
        if (isset($data['tel'])) $chauffeur->setTel($data['tel']);
        if (isset($data['nomSociete'])) $chauffeur->setNomSociete($data['nomSociete']);
        if (isset($data['vehicle'])) $chauffeur->setVehicle($data['vehicle']);
        if (isset($data['permis'])) $chauffeur->setPermis($data['permis']);
        
        // Seul l'admin peut modifier l'email et le SIRET
        if ($this->isGranted('ROLE_ADMIN')) {
            if (isset($data['email'])) $chauffeur->setEmail($data['email']);
            if (isset($data['siret'])) $chauffeur->setSiret($data['siret']);
            if (isset($data['kbis'])) $chauffeur->setKbis($data['kbis']);
            if (isset($data['carteVtc'])) $chauffeur->setCarteVtc($data['carteVtc']);
        }
        
        $this->em->flush();
        
        return new JsonResponse([
            'message' => 'Chauffeur mis à jour avec succès',
        ]);
    }

    /**
     * Supprimer un chauffeur (Admin only)
     */
    #[Route('/{id}', name: 'api_chauffeur_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $chauffeur = $this->chauffeurRepository->find($id);
        
        if (!$chauffeur) {
            return new JsonResponse(['error' => 'Chauffeur non trouvé'], 404);
        }
        
        $conn = $this->em->getConnection();
        
        try {
            // Commencer une transaction
            $conn->beginTransaction();
            
            // Supprimer les avis donnés et reçus
            $conn->executeStatement('DELETE FROM avis WHERE auteur_id = ? OR chauffeur_note_id = ?', [$id, $id]);
            
            // Mettre à null les références dans les courses
            $conn->executeStatement('UPDATE course SET chauffeur_vendeur_id = NULL WHERE chauffeur_vendeur_id = ?', [$id]);
            $conn->executeStatement('UPDATE course SET chauffeur_accepteur_id = NULL WHERE chauffeur_accepteur_id = ?', [$id]);
            
            // Supprimer les messages
            $conn->executeStatement('DELETE FROM message WHERE expediteur_id = ?', [$id]);
            
            // Supprimer les transactions
            $conn->executeStatement('DELETE FROM transaction WHERE chauffeur_payeur_id = ? OR chauffeur_receveur_id = ?', [$id, $id]);
            
            // Supprimer les favoris (table de liaison)
            $conn->executeStatement('DELETE FROM chauffeur_chauffeur WHERE chauffeur_source = ? OR chauffeur_target = ?', [$id, $id]);
            
            // Supprimer les rôles personnalisés (table de liaison)
            $conn->executeStatement('DELETE FROM chauffeur_roles WHERE chauffeur_id = ?', [$id]);
            
            // Supprimer les documents et mettre à null le validateur
            $conn->executeStatement('UPDATE document SET validated_by_id = NULL WHERE validated_by_id = ?', [$id]);
            $conn->executeStatement('DELETE FROM document WHERE chauffeur_id = ?', [$id]);
            
            // Supprimer les activity logs
            $conn->executeStatement('DELETE FROM activity_log WHERE chauffeur_id = ?', [$id]);
            
            // Supprimer les factures
            $conn->executeStatement('DELETE FROM facture WHERE emetteur_id = ? OR destinataire_id = ?', [$id, $id]);
            
            // Supprimer les invitations de groupe (invite_par_id et chauffeur_invite_id)
            $conn->executeStatement('DELETE FROM groupe_invitation WHERE invite_par_id = ? OR chauffeur_invite_id = ?', [$id, $id]);
            
            // Supprimer les membres de groupe (chauffeur_id et invite_par_id)
            $conn->executeStatement('DELETE FROM groupe_membre WHERE chauffeur_id = ? OR invite_par_id = ?', [$id, $id]);
            
            // Supprimer les groupes dont ce chauffeur est propriétaire
            // D'abord supprimer les courses liées aux groupes de ce chauffeur
            $conn->executeStatement('UPDATE course SET groupe_id = NULL WHERE groupe_id IN (SELECT id FROM groupe WHERE proprietaire_id = ?)', [$id]);
            $conn->executeStatement('UPDATE ride SET groupe_id = NULL WHERE groupe_id IN (SELECT id FROM groupe WHERE proprietaire_id = ?)', [$id]);
            // Puis les invitations et membres des groupes
            $conn->executeStatement('DELETE FROM groupe_invitation WHERE groupe_id IN (SELECT id FROM groupe WHERE proprietaire_id = ?)', [$id]);
            $conn->executeStatement('DELETE FROM groupe_membre WHERE groupe_id IN (SELECT id FROM groupe WHERE proprietaire_id = ?)', [$id]);
            // Enfin supprimer les groupes
            $conn->executeStatement('DELETE FROM groupe WHERE proprietaire_id = ?', [$id]);
            
            // Mettre à null les rides (chauffeur et chauffeur_accepteur)
            $conn->executeStatement('UPDATE ride SET chauffeur_id = NULL WHERE chauffeur_id = ?', [$id]);
            $conn->executeStatement('UPDATE ride SET chauffeur_accepteur_id = NULL WHERE chauffeur_accepteur_id = ?', [$id]);
            
            // Finalement supprimer le chauffeur directement en SQL
            $conn->executeStatement('DELETE FROM chauffeur WHERE id = ?', [$id]);
            
            // Valider la transaction
            $conn->commit();
            
            // Vider le cache Doctrine pour éviter les problèmes
            $this->em->clear();
            
            return new JsonResponse(['message' => 'Chauffeur supprimé'], 200);
        } catch (\Exception $e) {
            // Annuler en cas d'erreur
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            return new JsonResponse([
                'error' => 'Impossible de supprimer ce chauffeur',
                'details' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Statistiques d'un chauffeur
     */
    #[Route('/{id}/stats', name: 'api_chauffeur_stats', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function stats(int $id): JsonResponse
    {
        $chauffeur = $this->chauffeurRepository->find($id);
        
        if (!$chauffeur) {
            return new JsonResponse(['error' => 'Chauffeur non trouvé'], 404);
        }
        
        $rides = $this->rideRepository->findBy(['chauffeur' => $chauffeur]);
        $completedRides = array_filter($rides, fn($r) => $r->getStatus() === 'completed');
        
        $totalRevenue = array_reduce($completedRides, function($total, $ride) {
            return $total + ($ride->getPrice() ?? 0);
        }, 0);
        
        return new JsonResponse([
            'totalRides' => count($rides),
            'completedRides' => count($completedRides),
            'totalRevenue' => $totalRevenue,
            'rating' => 4.5,
            'monthlyData' => [
                ['month' => 'Jan', 'rides' => 5, 'revenue' => 450],
                ['month' => 'Fév', 'rides' => 8, 'revenue' => 720],
                ['month' => 'Mar', 'rides' => 12, 'revenue' => 1080],
                ['month' => 'Avr', 'rides' => 10, 'revenue' => 900],
                ['month' => 'Mai', 'rides' => 15, 'revenue' => 1350],
                ['month' => 'Juin', 'rides' => 18, 'revenue' => 1620],
            ],
        ]);
    }

    /**
     * 💰 Revenus du chauffeur connecté
     * Séparé en 2 catégories:
     * - Courses déposées: courses où le chauffeur est propriétaire (chauffeur)
     * - Courses effectuées: courses où le chauffeur est l'exécutant (chauffeurAccepteur)
     */
    #[Route('/earnings/me', name: 'api_chauffeur_earnings', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function earnings(Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        $period = $request->query->get('period', 'month'); // week, month, year, all
        
        // Calculer les dates selon la période
        $now = new \DateTime();
        $startDate = match($period) {
            'week' => (clone $now)->modify('-7 days'),
            'month' => (clone $now)->modify('first day of this month'),
            'year' => (clone $now)->modify('first day of january this year'),
            'all' => null, // Pas de filtre de date
            default => (clone $now)->modify('first day of this month'),
        };
        
        // ============================
        // COURSES EFFECTUÉES (gains)
        // ============================
        $qbPerformed = $this->rideRepository->createQueryBuilder('r')
            ->leftJoin('r.escrowPayment', 'e')
            ->where('r.chauffeurAccepteur = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'terminée')
            ->orderBy('r.date', 'DESC');
        
        if ($startDate) {
            $qbPerformed->andWhere('r.date >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        
        $performedRides = $qbPerformed->getQuery()->getResult();
        
        $performedCount = count($performedRides);
        $performedTotalEarned = 0;
        $performedList = [];
        $pendingAmount = 0;
        $availableAmount = 0;
        
        foreach ($performedRides as $ride) {
            $escrow = $ride->getEscrowPayment();
            $rideAmount = $ride->getPrice() ?? 0;
            $commission = $rideAmount * 0.15; // 15% commission WayZo
            $netAmount = $rideAmount - $commission;
            
            $performedTotalEarned += $netAmount;
            
            $escrowStatus = $escrow ? $escrow->getStatus() : 'pending';
            
            // Catégoriser par statut escrow
            if ($escrowStatus === 'completed' || $escrowStatus === 'paid') {
                $availableAmount += $netAmount;
            } elseif ($escrowStatus === 'held' || $escrowStatus === 'awaiting_validation') {
                $pendingAmount += $netAmount;
            }
            
            $performedList[] = [
                'id' => $ride->getId(),
                'prix' => $rideAmount,
                'net' => $netAmount,
                'commission' => $commission,
                'statut' => $ride->getStatus(),
                'escrowStatus' => $escrowStatus,
                'destination' => $ride->getDestination(),
                'createdAt' => $ride->getDate()?->format('c'),
            ];
        }
        
        // ============================
        // COURSES DÉPOSÉES (dépenses)
        // ============================
        $qbDeposited = $this->rideRepository->createQueryBuilder('r')
            ->where('r.chauffeur = :user')
            ->andWhere('r.chauffeurAccepteur IS NOT NULL') // Acceptée par quelqu'un d'autre
            ->andWhere('r.chauffeurAccepteur != :user') // Pas soi-même
            ->setParameter('user', $user)
            ->orderBy('r.date', 'DESC');
        
        if ($startDate) {
            $qbDeposited->andWhere('r.date >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        
        $depositedRides = $qbDeposited->getQuery()->getResult();
        
        $depositedCount = count($depositedRides);
        $depositedTotalPaid = 0;
        $depositedCommission = 0;
        $depositedList = [];
        
        foreach ($depositedRides as $ride) {
            $rideAmount = $ride->getPrice() ?? 0;
            $commission = $rideAmount * 0.15; // 15% commission WayZo
            $totalPaid = $rideAmount + $commission; // Prix + commission
            
            $depositedTotalPaid += $totalPaid;
            $depositedCommission += $commission;
            
            $depositedList[] = [
                'id' => $ride->getId(),
                'prix' => $rideAmount,
                'commission' => $commission,
                'totalPaid' => $totalPaid,
                'statut' => $ride->getStatus(),
                'destination' => $ride->getDestination(),
                'createdAt' => $ride->getDate()?->format('c'),
            ];
        }
        
        // Commission totale (payée sur les dépôts)
        $totalCommission = $depositedCommission;
        
        return new JsonResponse([
            // Statistiques globales
            'totalEarned' => round($performedTotalEarned, 2),
            'pendingAmount' => round($pendingAmount, 2),
            'availableAmount' => round($availableAmount, 2),
            'totalCommission' => round($totalCommission, 2),
            
            // Courses effectuées (gains)
            'performedRides' => [
                'count' => $performedCount,
                'totalEarned' => round($performedTotalEarned, 2),
                'rides' => array_slice($performedList, 0, 10), // 10 dernières
            ],
            
            // Courses déposées (dépenses)
            'depositedRides' => [
                'count' => $depositedCount,
                'totalPaid' => round($depositedTotalPaid, 2),
                'rides' => array_slice($depositedList, 0, 10), // 10 dernières
            ],
        ]);
    }
    
    /**
     * Tronquer une adresse pour l'affichage
     */
    private function truncateAddress(?string $address): string
    {
        if (!$address) return '';
        return strlen($address) > 30 ? substr($address, 0, 30) . '...' : $address;
    }
    
    /**
     * Générer les données du graphique selon la période
     */
    private function getChartData(Chauffeur $user, string $period, \DateTime $startDate): array
    {
        $labels = [];
        $values = [];
        
        if ($period === 'week') {
            // 7 derniers jours
            for ($i = 6; $i >= 0; $i--) {
                $date = (clone $startDate)->modify("-{$i} days + 6 days");
                $labels[] = $date->format('D');
                
                $dayTotal = $this->getDayTotal($user, $date);
                $values[] = round($dayTotal * 0.85, 2); // Net
            }
        } elseif ($period === 'month') {
            // Semaines du mois
            $weekLabels = ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'];
            for ($week = 0; $week < 4; $week++) {
                $labels[] = $weekLabels[$week];
                $weekStart = (clone $startDate)->modify("+{$week} weeks");
                $weekEnd = (clone $weekStart)->modify('+6 days');
                
                $weekTotal = $this->getWeekTotal($user, $weekStart, $weekEnd);
                $values[] = round($weekTotal * 0.85, 2);
            }
        } else {
            // Mois de l'année
            $monthLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            $currentMonth = (int)(new \DateTime())->format('n');
            
            for ($m = 1; $m <= $currentMonth; $m++) {
                $labels[] = $monthLabels[$m - 1];
                $monthTotal = $this->getMonthTotal($user, $m);
                $values[] = round($monthTotal * 0.85, 2);
            }
        }
        
        return ['labels' => $labels, 'values' => $values];
    }
    
    private function getDayTotal(Chauffeur $user, \DateTime $date): float
    {
        $result = $this->rideRepository->createQueryBuilder('r')
            ->select('SUM(r.price)')
            ->where('r.chauffeurAccepteur = :user')
            ->andWhere('r.status = :status')
            ->andWhere('DATE(r.date) = :date')
            ->setParameter('user', $user)
            ->setParameter('status', 'terminée')
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();
        
        return (float)($result ?? 0);
    }
    
    private function getWeekTotal(Chauffeur $user, \DateTime $start, \DateTime $end): float
    {
        $result = $this->rideRepository->createQueryBuilder('r')
            ->select('SUM(r.price)')
            ->where('r.chauffeurAccepteur = :user')
            ->andWhere('r.status = :status')
            ->andWhere('r.date >= :start')
            ->andWhere('r.date <= :end')
            ->setParameter('user', $user)
            ->setParameter('status', 'terminée')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
        
        return (float)($result ?? 0);
    }
    
    private function getMonthTotal(Chauffeur $user, int $month): float
    {
        $year = (new \DateTime())->format('Y');
        $result = $this->rideRepository->createQueryBuilder('r')
            ->select('SUM(r.price)')
            ->where('r.chauffeurAccepteur = :user')
            ->andWhere('r.status = :status')
            ->andWhere('MONTH(r.date) = :month')
            ->andWhere('YEAR(r.date) = :year')
            ->setParameter('user', $user)
            ->setParameter('status', 'terminée')
            ->setParameter('month', $month)
            ->setParameter('year', $year)
            ->getQuery()
            ->getSingleScalarResult();
        
        return (float)($result ?? 0);
    }
}
