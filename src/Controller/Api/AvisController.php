<?php

namespace App\Controller\Api;

use App\Entity\Avis;
use App\Repository\AvisRepository;
use App\Repository\ChauffeurRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/avis')]
class AvisController extends BaseApiController
{
    public function __construct(
        private AvisRepository $avisRepository,
        private ChauffeurRepository $chauffeurRepository,
        private CourseRepository $courseRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Créer un avis pour une course terminée
     */
    #[Route('', name: 'api_avis_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getChauffeur();

        // Validation des données
        if (!isset($data['courseId']) || !isset($data['note'])) {
            return new JsonResponse([
                'error' => 'Les champs courseId et note sont requis'
            ], 400);
        }

        $note = (int) $data['note'];
        if ($note < Avis::NOTE_MIN || $note > Avis::NOTE_MAX) {
            return new JsonResponse([
                'error' => sprintf('La note doit être entre %d et %d', Avis::NOTE_MIN, Avis::NOTE_MAX)
            ], 400);
        }

        // Récupérer la course
        $course = $this->courseRepository->find($data['courseId']);
        if (!$course) {
            return new JsonResponse(['error' => 'Course non trouvée'], 404);
        }

        // Vérifier que la course est terminée
        if ($course->getStatutExecution() !== 'terminee') {
            return new JsonResponse([
                'error' => 'Vous ne pouvez noter que les courses terminées'
            ], 400);
        }

        // Vérifier qu'il n'y a pas déjà un avis
        if ($course->hasAvis()) {
            return new JsonResponse([
                'error' => 'Cette course a déjà été notée'
            ], 400);
        }

        // Vérifier que l'utilisateur est bien le chauffeur accepteur (celui qui a exécuté)
        if ($course->getChauffeurAccepteur()?->getId() !== $user->getId()) {
            return new JsonResponse([
                'error' => 'Seul le chauffeur ayant exécuté la course peut donner un avis'
            ], 403);
        }

        // Le chauffeur noté est le vendeur
        $chauffeurNote = $course->getChauffeurVendeur();
        if (!$chauffeurNote) {
            return new JsonResponse([
                'error' => 'Impossible de déterminer le chauffeur à noter'
            ], 400);
        }

        // Créer l'avis
        $avis = new Avis();
        $avis->setNote($note);
        $avis->setCommentaire($data['commentaire'] ?? null);
        $avis->setAuteur($user);
        $avis->setChauffeurNote($chauffeurNote);
        $avis->setCourse($course);

        $this->em->persist($avis);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Avis enregistré avec succès',
            'avis' => $avis->toArray()
        ], 201);
    }

    /**
     * Liste des avis d'un chauffeur
     */
    #[Route('/chauffeur/{id}', name: 'api_avis_chauffeur', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getAvisChauffeur(int $id, Request $request): JsonResponse
    {
        $chauffeur = $this->chauffeurRepository->find($id);
        
        if (!$chauffeur) {
            return new JsonResponse(['error' => 'Chauffeur non trouvé'], 404);
        }

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $result = $this->avisRepository->findByChaufffeurPaginated($chauffeur, $page, $limit);
        $avgRating = $this->avisRepository->getAverageRating($chauffeur);
        $distribution = $this->avisRepository->getRatingDistribution($chauffeur);

        return new JsonResponse([
            'chauffeur' => [
                'id' => $chauffeur->getId(),
                'nom' => $chauffeur->getNom(),
                'prenom' => $chauffeur->getPrenom(),
            ],
            'stats' => [
                'averageRating' => $avgRating ?? 0,
                'totalAvis' => $result['total'],
                'distribution' => $distribution,
            ],
            'avis' => array_map(fn(Avis $a) => $a->toArray(), $result['avis']),
            'pagination' => [
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
                'total' => $result['total'],
            ],
        ]);
    }

    /**
     * Mes avis (reçus)
     */
    #[Route('/mes-avis', name: 'api_avis_mes_avis', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mesAvis(Request $request): JsonResponse
    {
        $user = $this->getChauffeur();
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $result = $this->avisRepository->findByChaufffeurPaginated($user, $page, $limit);
        $avgRating = $this->avisRepository->getAverageRating($user);
        $distribution = $this->avisRepository->getRatingDistribution($user);

        return new JsonResponse([
            'stats' => [
                'averageRating' => $avgRating ?? 0,
                'totalAvis' => $result['total'],
                'distribution' => $distribution,
            ],
            'avis' => array_map(fn(Avis $a) => $a->toArray(), $result['avis']),
            'pagination' => [
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
                'total' => $result['total'],
            ],
        ]);
    }

    /**
     * Avis donnés par moi
     */
    #[Route('/mes-avis-donnes', name: 'api_avis_donnes', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mesAvisDonnes(): JsonResponse
    {
        $user = $this->getChauffeur();
        
        $avis = $this->avisRepository->findBy(
            ['auteur' => $user],
            ['createdAt' => 'DESC']
        );

        return new JsonResponse([
            'total' => count($avis),
            'avis' => array_map(fn(Avis $a) => $a->toArray(), $avis),
        ]);
    }

    /**
     * Vérifier si une course peut être notée
     */
    #[Route('/can-rate/{courseId}', name: 'api_avis_can_rate', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function canRate(int $courseId): JsonResponse
    {
        $user = $this->getChauffeur();
        $course = $this->courseRepository->find($courseId);

        if (!$course) {
            return new JsonResponse(['canRate' => false, 'reason' => 'Course non trouvée']);
        }

        // Vérifier que c'est le bon chauffeur
        if ($course->getChauffeurAccepteur()?->getId() !== $user->getId()) {
            return new JsonResponse([
                'canRate' => false,
                'reason' => 'Vous n\'êtes pas autorisé à noter cette course'
            ]);
        }

        // Vérifier que la course est terminée
        if ($course->getStatutExecution() !== 'terminee') {
            return new JsonResponse([
                'canRate' => false,
                'reason' => 'La course n\'est pas encore terminée'
            ]);
        }

        // Vérifier qu'il n'y a pas déjà un avis
        if ($course->hasAvis()) {
            return new JsonResponse([
                'canRate' => false,
                'reason' => 'Cette course a déjà été notée',
                'existingAvis' => $course->getAvis()->toArray()
            ]);
        }

        $vendeur = $course->getChauffeurVendeur();
        return new JsonResponse([
            'canRate' => true,
            'vendeur' => [
                'id' => $vendeur?->getId(),
                'nom' => $vendeur?->getNom(),
                'prenom' => $vendeur?->getPrenom(),
            ]
        ]);
    }

    /**
     * Statistiques globales des avis (admin)
     */
    #[Route('/stats', name: 'api_avis_stats', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function stats(): JsonResponse
    {
        $globalStats = $this->avisRepository->getGlobalStats();
        $latestAvis = $this->avisRepository->findLatest(10);

        return new JsonResponse([
            'global' => $globalStats,
            'latest' => array_map(fn(Avis $a) => $a->toArray(), $latestAvis),
        ]);
    }
}
