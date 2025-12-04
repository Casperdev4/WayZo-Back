<?php

namespace App\Controller\Api;

use App\Entity\Chauffeur;
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
                'status' => 'active',
                'rating' => 4.5, // TODO: Calculer depuis les avis
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
            'status' => 'active',
            'rating' => 4.5,
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
        
        $this->em->remove($chauffeur);
        $this->em->flush();
        
        return new JsonResponse(['message' => 'Chauffeur supprimé'], 200);
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
}
