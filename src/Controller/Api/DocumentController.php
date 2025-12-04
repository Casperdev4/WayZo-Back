<?php

namespace App\Controller\Api;

use App\Entity\Document;
use App\Entity\Chauffeur;
use App\Repository\DocumentRepository;
use App\Repository\ChauffeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/documents')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DocumentController extends AbstractController
{
    private string $uploadDirectory;

    public function __construct(
        private DocumentRepository $documentRepository,
        private ChauffeurRepository $chauffeurRepository,
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
    ) {
        $this->uploadDirectory = dirname(__DIR__, 3) . '/public/uploads/documents';
    }

    /**
     * Liste tous les documents de l'utilisateur courant
     */
    #[Route('', name: 'api_documents_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $type = $request->query->get('type');
        $status = $request->query->get('status');
        $search = $request->query->get('search');

        // Admin peut voir tous les documents
        $chauffeur = $this->isGranted('ROLE_ADMIN') ? null : $user;

        $result = $this->documentRepository->searchWithPagination(
            $page,
            $limit,
            $search,
            $type,
            $status,
            $chauffeur
        );

        return $this->json([
            'data' => array_map(fn($d) => $d->toArray(), $result['data']),
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    /**
     * Liste les documents d'un chauffeur spécifique (admin)
     */
    #[Route('/chauffeur/{id}', name: 'api_documents_by_chauffeur', methods: ['GET'])]
    public function listByChauffeur(int $id): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        // Autoriser l'accès au propriétaire ou admin
        if (!$this->isGranted('ROLE_ADMIN') && $user->getId() !== $id) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $chauffeur = $this->chauffeurRepository->find($id);
        if (!$chauffeur) {
            return $this->json(['error' => 'Chauffeur non trouvé'], 404);
        }

        $documents = $this->documentRepository->findByChauffeur($chauffeur);

        return $this->json([
            'chauffeur' => [
                'id' => $chauffeur->getId(),
                'name' => $chauffeur->getFullName(),
            ],
            'documents' => array_map(fn($d) => $d->toArray(), $documents),
        ]);
    }

    /**
     * Récupère les détails d'un document
     */
    #[Route('/{id}', name: 'api_documents_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document non trouvé'], 404);
        }

        // Vérifier l'accès
        if (!$this->isGranted('ROLE_ADMIN') && $document->getChauffeur()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        return $this->json($document->toArray());
    }

    /**
     * Upload un nouveau document
     */
    #[Route('', name: 'api_documents_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $file = $request->files->get('file');
        $type = $request->request->get('type');
        $description = $request->request->get('description');
        $expiresAt = $request->request->get('expiresAt');

        if (!$file) {
            return $this->json(['error' => 'Aucun fichier fourni'], 400);
        }

        if (!$type || !in_array($type, Document::ALLOWED_TYPES)) {
            return $this->json(['error' => 'Type de document invalide'], 400);
        }

        // Vérifier le type MIME
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->json(['error' => 'Type de fichier non autorisé (PDF, JPEG, PNG, WebP)'], 400);
        }

        // Vérifier la taille (max 10Mo)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->json(['error' => 'Fichier trop volumineux (max 10Mo)'], 400);
        }

        // Créer le répertoire si nécessaire
        $userDir = $this->uploadDirectory . '/' . $user->getId();
        if (!is_dir($userDir)) {
            mkdir($userDir, 0755, true);
        }

        // Générer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Déplacer le fichier
        try {
            $file->move($userDir, $newFilename);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'upload: ' . $e->getMessage()], 500);
        }

        // Créer l'entité Document
        $document = new Document();
        $document->setChauffeur($user);
        $document->setType($type);
        $document->setOriginalName($file->getClientOriginalName());
        $document->setFilename($newFilename);
        $document->setMimeType($file->getMimeType());
        $document->setSize($file->getSize());
        $document->setDescription($description);

        if ($expiresAt) {
            $document->setExpiresAt(new \DateTimeImmutable($expiresAt));
        }

        $this->documentRepository->save($document, true);

        return $this->json($document->toArray(), 201);
    }

    /**
     * Met à jour un document (description, date d'expiration)
     */
    #[Route('/{id}', name: 'api_documents_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document non trouvé'], 404);
        }

        // Vérifier l'accès
        if (!$this->isGranted('ROLE_ADMIN') && $document->getChauffeur()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['description'])) {
            $document->setDescription($data['description']);
        }

        if (isset($data['expiresAt'])) {
            $document->setExpiresAt($data['expiresAt'] ? new \DateTimeImmutable($data['expiresAt']) : null);
        }

        $this->em->flush();

        return $this->json($document->toArray());
    }

    /**
     * Supprime un document
     */
    #[Route('/{id}', name: 'api_documents_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document non trouvé'], 404);
        }

        // Vérifier l'accès
        if (!$this->isGranted('ROLE_ADMIN') && $document->getChauffeur()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        // Supprimer le fichier physique
        $filePath = $this->uploadDirectory . '/' . $document->getChauffeur()->getId() . '/' . $document->getFilename();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->documentRepository->remove($document, true);

        return $this->json(['message' => 'Document supprimé']);
    }

    /**
     * Télécharge un document
     */
    #[Route('/{id}/download', name: 'api_documents_download', methods: ['GET'])]
    public function download(int $id): Response
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document non trouvé'], 404);
        }

        // Vérifier l'accès
        if (!$this->isGranted('ROLE_ADMIN') && $document->getChauffeur()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $filePath = $this->uploadDirectory . '/' . $document->getChauffeur()->getId() . '/' . $document->getFilename();

        if (!file_exists($filePath)) {
            return $this->json(['error' => 'Fichier non trouvé sur le serveur'], 404);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getOriginalName()
        );

        return $response;
    }

    /**
     * Prévisualise un document (pour images et PDF)
     */
    #[Route('/{id}/preview', name: 'api_documents_preview', methods: ['GET'])]
    public function preview(int $id): Response
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document non trouvé'], 404);
        }

        // Vérifier l'accès
        if (!$this->isGranted('ROLE_ADMIN') && $document->getChauffeur()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $filePath = $this->uploadDirectory . '/' . $document->getChauffeur()->getId() . '/' . $document->getFilename();

        if (!file_exists($filePath)) {
            return $this->json(['error' => 'Fichier non trouvé sur le serveur'], 404);
        }

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $document->getMimeType());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $document->getOriginalName()
        );

        return $response;
    }

    /**
     * Valide un document (admin)
     */
    #[Route('/{id}/approve', name: 'api_documents_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(int $id): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document non trouvé'], 404);
        }

        $document->approve($user);
        $this->em->flush();

        return $this->json([
            'message' => 'Document approuvé',
            'document' => $document->toArray(),
        ]);
    }

    /**
     * Rejette un document (admin)
     */
    #[Route('/{id}/reject', name: 'api_documents_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(int $id, Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Document non conforme';

        $document->reject($user, $reason);
        $this->em->flush();

        return $this->json([
            'message' => 'Document rejeté',
            'document' => $document->toArray(),
        ]);
    }

    /**
     * Partage un document (génère un lien de partage)
     */
    #[Route('/{id}/share', name: 'api_documents_share', methods: ['POST'])]
    public function share(int $id, Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document non trouvé'], 404);
        }

        // Vérifier l'accès
        if ($document->getChauffeur()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $daysValid = $data['daysValid'] ?? 7;

        $token = $document->generateShareToken($daysValid);
        $this->em->flush();

        return $this->json([
            'message' => 'Lien de partage créé',
            'shareUrl' => '/api/documents/shared/' . $token,
            'expiresAt' => $document->getShareExpiresAt()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Révoque le partage d'un document
     */
    #[Route('/{id}/revoke-share', name: 'api_documents_revoke_share', methods: ['POST'])]
    public function revokeShare(int $id): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $document = $this->documentRepository->find($id);

        if (!$document) {
            return $this->json(['error' => 'Document non trouvé'], 404);
        }

        // Vérifier l'accès
        if ($document->getChauffeur()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $document->revokeShare();
        $this->em->flush();

        return $this->json(['message' => 'Partage révoqué']);
    }

    /**
     * Accède à un document partagé (public)
     */
    #[Route('/shared/{token}', name: 'api_documents_shared', methods: ['GET'])]
    public function viewShared(string $token): Response
    {
        $document = $this->documentRepository->findByShareToken($token);

        if (!$document) {
            return $this->json(['error' => 'Document non trouvé ou lien expiré'], 404);
        }

        if ($document->isShareExpired()) {
            return $this->json(['error' => 'Ce lien de partage a expiré'], 410);
        }

        $filePath = $this->uploadDirectory . '/' . $document->getChauffeur()->getId() . '/' . $document->getFilename();

        if (!file_exists($filePath)) {
            return $this->json(['error' => 'Fichier non trouvé'], 404);
        }

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $document->getMimeType());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $document->getOriginalName()
        );

        return $response;
    }

    /**
     * Obtient les types de documents disponibles
     */
    #[Route('/types', name: 'api_documents_types', methods: ['GET'])]
    public function getTypes(): JsonResponse
    {
        $types = [];
        foreach (Document::TYPE_LABELS as $key => $label) {
            $types[] = [
                'value' => $key,
                'label' => $label,
            ];
        }

        return $this->json($types);
    }

    /**
     * Obtient les statistiques des documents
     */
    #[Route('/stats', name: 'api_documents_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            // Stats globales pour admin
            $stats = $this->documentRepository->getStats();
        } else {
            // Stats personnelles pour le chauffeur
            $documents = $this->documentRepository->findByChauffeur($user);
            $stats = [
                'total' => count($documents),
                'pending' => count(array_filter($documents, fn($d) => $d->isPending())),
                'approved' => count(array_filter($documents, fn($d) => $d->isApproved())),
                'rejected' => count(array_filter($documents, fn($d) => $d->isRejected())),
                'expired' => count(array_filter($documents, fn($d) => $d->isExpired())),
            ];
        }

        return $this->json($stats);
    }

    /**
     * Documents en attente de validation (admin)
     */
    #[Route('/pending', name: 'api_documents_pending', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function pending(): JsonResponse
    {
        $documents = $this->documentRepository->findPendingDocuments();

        return $this->json(array_map(fn($d) => $d->toArray(), $documents));
    }

    /**
     * Documents qui vont expirer bientôt
     */
    #[Route('/expiring', name: 'api_documents_expiring', methods: ['GET'])]
    public function expiring(Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $days = $request->query->getInt('days', 30);

        $documents = $this->documentRepository->findExpiringDocuments($days);

        // Filtrer selon les droits
        if (!$this->isGranted('ROLE_ADMIN')) {
            $documents = array_filter($documents, fn($d) => $d->getChauffeur()->getId() === $user->getId());
        }

        return $this->json(array_map(fn($d) => $d->toArray(), array_values($documents)));
    }

    /**
     * Upload multiple documents
     */
    #[Route('/bulk-upload', name: 'api_documents_bulk_upload', methods: ['POST'])]
    public function bulkUpload(Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();

        $files = $request->files->get('files');
        $types = $request->request->all('types');

        if (!$files || !is_array($files)) {
            return $this->json(['error' => 'Aucun fichier fourni'], 400);
        }

        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            $type = $types[$index] ?? Document::TYPE_AUTRE;

            if (!in_array($type, Document::ALLOWED_TYPES)) {
                $errors[] = ['file' => $file->getClientOriginalName(), 'error' => 'Type invalide'];
                continue;
            }

            $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                $errors[] = ['file' => $file->getClientOriginalName(), 'error' => 'Type MIME non autorisé'];
                continue;
            }

            if ($file->getSize() > 10 * 1024 * 1024) {
                $errors[] = ['file' => $file->getClientOriginalName(), 'error' => 'Fichier trop volumineux'];
                continue;
            }

            try {
                $userDir = $this->uploadDirectory . '/' . $user->getId();
                if (!is_dir($userDir)) {
                    mkdir($userDir, 0755, true);
                }

                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

                $file->move($userDir, $newFilename);

                $document = new Document();
                $document->setChauffeur($user);
                $document->setType($type);
                $document->setOriginalName($file->getClientOriginalName());
                $document->setFilename($newFilename);
                $document->setMimeType($file->getMimeType());
                $document->setSize($file->getSize());

                $this->documentRepository->save($document, true);

                $results[] = $document->toArray();
            } catch (\Exception $e) {
                $errors[] = ['file' => $file->getClientOriginalName(), 'error' => $e->getMessage()];
            }
        }

        return $this->json([
            'uploaded' => $results,
            'errors' => $errors,
            'totalUploaded' => count($results),
            'totalErrors' => count($errors),
        ], count($results) > 0 ? 201 : 400);
    }
}
