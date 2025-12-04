<?php

namespace App\Controller\Api;

use App\Entity\Facture;
use App\Entity\Course;
use App\Repository\FactureRepository;
use App\Repository\CourseRepository;
use App\Service\FacturationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/api/factures')]
class FactureController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private FactureRepository $factureRepository,
        private CourseRepository $courseRepository,
        private FacturationService $facturationService,
        private ParameterBagInterface $params
    ) {}

    /**
     * Liste des factures du chauffeur connecté
     */
    #[Route('', name: 'api_factures_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $type = $request->query->get('type'); // 'emises', 'recues', 'all'
        
        if ($type === 'emises') {
            $factures = $this->factureRepository->findByEmetteur($user);
        } elseif ($type === 'recues') {
            $factures = $this->factureRepository->findByDestinataire($user);
        } else {
            $factures = $this->factureRepository->findAllByChaufffeur($user);
        }

        $data = array_map(fn($f) => $this->serializeFacture($f), $factures);

        return new JsonResponse($data);
    }

    /**
     * Détail d'une facture
     */
    #[Route('/{id}', name: 'api_factures_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Facture $facture): JsonResponse
    {
        $user = $this->getUser();
        
        // Vérifier l'accès
        if ($facture->getEmetteur() !== $user && $facture->getDestinataire() !== $user) {
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        return new JsonResponse($this->serializeFacture($facture, true));
    }

    /**
     * Statistiques de facturation
     */
    #[Route('/stats', name: 'api_factures_stats', methods: ['GET'], priority: 10)]
    #[IsGranted('ROLE_USER')]
    public function stats(): JsonResponse
    {
        $user = $this->getUser();
        $stats = $this->facturationService->getStats($user);

        return new JsonResponse($stats);
    }

    /**
     * Factures en attente de paiement
     */
    #[Route('/pending', name: 'api_factures_pending', methods: ['GET'], priority: 10)]
    #[IsGranted('ROLE_USER')]
    public function pending(): JsonResponse
    {
        $user = $this->getUser();
        $factures = $this->factureRepository->findPendingByDestinataire($user);

        $data = array_map(fn($f) => $this->serializeFacture($f), $factures);

        return new JsonResponse($data);
    }

    /**
     * Factures d'une course spécifique
     */
    #[Route('/course/{courseId}', name: 'api_factures_by_course', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function byCourse(int $courseId): JsonResponse
    {
        $factures = $this->factureRepository->findByCourse($courseId);
        $user = $this->getUser();

        // Filtrer pour n'afficher que celles accessibles
        $accessibleFactures = array_filter($factures, function($f) use ($user) {
            return $f->getEmetteur() === $user || $f->getDestinataire() === $user;
        });

        $data = array_map(fn($f) => $this->serializeFacture($f), array_values($accessibleFactures));

        return new JsonResponse($data);
    }

    /**
     * Télécharger le PDF d'une facture
     */
    #[Route('/{id}/download', name: 'api_factures_download', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function download(Facture $facture): Response
    {
        $user = $this->getUser();
        
        // Vérifier l'accès
        if ($facture->getEmetteur() !== $user && $facture->getDestinataire() !== $user) {
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        // Générer le PDF si pas encore fait
        if (!$facture->getPdfPath()) {
            $this->facturationService->generatePDF($facture);
        }

        $projectDir = $this->params->get('kernel.project_dir');
        
        // Pour l'instant, on renvoie le HTML (car DOMPDF n'est peut-être pas installé)
        $htmlPath = $projectDir . '/public' . str_replace('.pdf', '.html', $facture->getPdfPath());
        
        if (file_exists($htmlPath)) {
            $response = new BinaryFileResponse($htmlPath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'facture_' . $facture->getNumero() . '.html'
            );
            return $response;
        }

        return new JsonResponse(['error' => 'Fichier non trouvé'], 404);
    }

    /**
     * Prévisualiser le PDF (affichage inline)
     */
    #[Route('/{id}/preview', name: 'api_factures_preview', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function preview(Facture $facture): Response
    {
        $user = $this->getUser();
        
        // Vérifier l'accès
        if ($facture->getEmetteur() !== $user && $facture->getDestinataire() !== $user) {
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        // Générer le HTML de la facture
        return $this->render('facture/pdf.html.twig', [
            'facture' => $facture,
        ]);
    }

    /**
     * Marquer une facture comme payée
     */
    #[Route('/{id}/pay', name: 'api_factures_pay', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function pay(Facture $facture): JsonResponse
    {
        $user = $this->getUser();
        
        // Seul le destinataire peut marquer comme payée
        if ($facture->getDestinataire() !== $user) {
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        if ($facture->getStatut() !== Facture::STATUT_ISSUED) {
            return new JsonResponse(['error' => 'Cette facture ne peut pas être marquée comme payée'], 400);
        }

        $this->facturationService->markAsPaid($facture);

        return new JsonResponse([
            'success' => true,
            'message' => 'Facture marquée comme payée',
            'facture' => $this->serializeFacture($facture)
        ]);
    }

    /**
     * Générer les factures pour une course terminée
     * (Appelé automatiquement quand une course est terminée)
     */
    #[Route('/generate/{courseId}', name: 'api_factures_generate', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generate(int $courseId): JsonResponse
    {
        $course = $this->courseRepository->find($courseId);
        
        if (!$course) {
            return new JsonResponse(['error' => 'Course non trouvée'], 404);
        }

        // Vérifier que la course est terminée
        if ($course->getStatutExecution() !== 'terminee') {
            return new JsonResponse(['error' => 'La course doit être terminée pour générer les factures'], 400);
        }

        // Vérifier qu'il n'y a pas déjà de factures
        $existingFactures = $this->factureRepository->findByCourse($courseId);
        if (count($existingFactures) > 0) {
            return new JsonResponse(['error' => 'Des factures existent déjà pour cette course'], 400);
        }

        // Récupérer la transaction associée
        $transactions = $course->getTransactions();
        $transaction = $transactions->count() > 0 ? $transactions->first() : null;

        try {
            $factures = $this->facturationService->createFacturesForCourse($course, $transaction);

            return new JsonResponse([
                'success' => true,
                'message' => 'Factures générées avec succès',
                'factures' => array_map(fn($f) => $this->serializeFacture($f), $factures)
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Sérialiser une facture
     */
    private function serializeFacture(Facture $facture, bool $full = false): array
    {
        $data = [
            'id' => $facture->getId(),
            'numero' => $facture->getNumero(),
            'type' => $facture->getType(),
            'typeLabel' => $facture->isPrestation() ? 'Prestation' : 'Sous-traitance',
            'dateEmission' => $facture->getDateEmission()?->format('Y-m-d'),
            'dateEcheance' => $facture->getDateEcheance()?->format('Y-m-d'),
            'montantHT' => floatval($facture->getMontantHT()),
            'tauxTVA' => floatval($facture->getTauxTVA()),
            'montantTVA' => floatval($facture->getMontantTVA()),
            'montantTTC' => floatval($facture->getMontantTTC()),
            'statut' => $facture->getStatut(),
            'statutLabel' => $this->getStatutLabel($facture->getStatut()),
            'emetteur' => [
                'id' => $facture->getEmetteur()->getId(),
                'nom' => $facture->getEmetteurInfo()['nomComplet'] ?? '',
                'societe' => $facture->getEmetteurInfo()['raisonSociale'] ?? '',
            ],
            'destinataire' => [
                'id' => $facture->getDestinataire()->getId(),
                'nom' => $facture->getDestinataireInfo()['nomComplet'] ?? '',
                'societe' => $facture->getDestinataireInfo()['raisonSociale'] ?? '',
            ],
            'courseId' => $facture->getCourse()?->getId(),
            'pdfPath' => $facture->getPdfPath(),
            'createdAt' => $facture->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($full) {
            $data['description'] = $facture->getDescription();
            $data['emetteurInfo'] = $facture->getEmetteurInfo();
            $data['destinataireInfo'] = $facture->getDestinataireInfo();
            $data['courseDetails'] = $facture->getCourseDetails();
        }

        return $data;
    }

    private function getStatutLabel(string $statut): string
    {
        return match($statut) {
            Facture::STATUT_DRAFT => 'Brouillon',
            Facture::STATUT_ISSUED => 'En attente',
            Facture::STATUT_PAID => 'Payée',
            Facture::STATUT_CANCELLED => 'Annulée',
            default => $statut,
        };
    }
}
