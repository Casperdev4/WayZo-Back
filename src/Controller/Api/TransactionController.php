<?php

namespace App\Controller\Api;

use App\Entity\Chauffeur;
use App\Entity\Transaction;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/api/transactions')]
class TransactionController extends AbstractController
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private Security $security
    ) {}

    /**
     * 📋 Liste des transactions de l'utilisateur connecté
     */
    #[Route('', name: 'api_transactions_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var Chauffeur|null $user */
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $userId = $user->getId();

        // Récupérer les transactions où l'utilisateur est payeur ou receveur
        $transactions = $this->transactionRepository->findByUser($userId);

        $data = array_map(function(Transaction $transaction) use ($userId) {
            $isPayeur = $transaction->getChauffeurPayeur()?->getId() === $userId;
            $otherChauffeur = $isPayeur 
                ? $transaction->getChauffeurReceveur() 
                : $transaction->getChauffeurPayeur();

            return [
                'id' => $transaction->getId(),
                'reference' => $transaction->getReference(),
                'montant' => $transaction->getMontant(),
                'date' => $transaction->getDate()?->format('Y-m-d H:i:s'),
                'statut' => $transaction->getStatut(),
                'type' => $isPayeur ? 'debit' : 'credit',
                'typeLabel' => $isPayeur ? 'Paiement envoyé' : 'Paiement reçu',
                'otherChauffeur' => $otherChauffeur ? [
                    'id' => $otherChauffeur->getId(),
                    'nom' => $otherChauffeur->getNom(),
                    'prenom' => $otherChauffeur->getPrenom(),
                    'avatar' => null, // Pas de champ avatar dans Chauffeur
                ] : null,
                'course' => $transaction->getCourse() ? [
                    'id' => $transaction->getCourse()->getId(),
                    'depart' => $transaction->getCourse()->getDepart(),
                    'arrivee' => $transaction->getCourse()->getArrivee(),
                ] : null,
                'completedAt' => $transaction->getCompletedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $transactions);

        return $this->json([
            'transactions' => $data,
            'total' => count($data),
        ]);
    }

    /**
     * 📊 Statistiques des transactions
     */
    #[Route('/stats', name: 'api_transactions_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        /** @var Chauffeur|null $user */
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $userId = $user->getId();
        $transactions = $this->transactionRepository->findByUser($userId);

        $totalCredits = 0;
        $totalDebits = 0;
        $pending = 0;
        $completed = 0;

        foreach ($transactions as $transaction) {
            $isPayeur = $transaction->getChauffeurPayeur()?->getId() === $userId;
            
            if ($transaction->getStatut() === Transaction::STATUT_COMPLETED) {
                if ($isPayeur) {
                    $totalDebits += $transaction->getMontant();
                } else {
                    $totalCredits += $transaction->getMontant();
                }
                $completed++;
            } elseif ($transaction->getStatut() === Transaction::STATUT_PENDING) {
                $pending++;
            }
        }

        return $this->json([
            'totalCredits' => $totalCredits,
            'totalDebits' => $totalDebits,
            'balance' => $totalCredits - $totalDebits,
            'pending' => $pending,
            'completed' => $completed,
            'total' => count($transactions),
        ]);
    }

    /**
     * 📄 Détails d'une transaction
     */
    #[Route('/{id}', name: 'api_transactions_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        /** @var Chauffeur|null $user */
        $user = $this->security->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $userId = $user->getId();
        $transaction = $this->transactionRepository->find($id);

        if (!$transaction) {
            return $this->json(['error' => 'Transaction non trouvée'], 404);
        }

        // Vérifier que l'utilisateur a accès à cette transaction
        $isPayeur = $transaction->getChauffeurPayeur()?->getId() === $userId;
        $isReceveur = $transaction->getChauffeurReceveur()?->getId() === $userId;

        if (!$isPayeur && !$isReceveur) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $otherChauffeur = $isPayeur 
            ? $transaction->getChauffeurReceveur() 
            : $transaction->getChauffeurPayeur();

        return $this->json([
            'id' => $transaction->getId(),
            'reference' => $transaction->getReference(),
            'montant' => $transaction->getMontant(),
            'date' => $transaction->getDate()?->format('Y-m-d H:i:s'),
            'statut' => $transaction->getStatut(),
            'type' => $isPayeur ? 'debit' : 'credit',
            'typeLabel' => $isPayeur ? 'Paiement envoyé' : 'Paiement reçu',
            'otherChauffeur' => $otherChauffeur ? [
                'id' => $otherChauffeur->getId(),
                'nom' => $otherChauffeur->getNom(),
                'prenom' => $otherChauffeur->getPrenom(),
                'email' => $otherChauffeur->getEmail(),
            ] : null,
            'course' => $transaction->getCourse() ? [
                'id' => $transaction->getCourse()->getId(),
                'depart' => $transaction->getCourse()->getDepart(),
                'arrivee' => $transaction->getCourse()->getArrivee(),
                'date' => $transaction->getCourse()->getDate()?->format('Y-m-d H:i:s'),
                'prix' => $transaction->getCourse()->getPrix(),
            ] : null,
            'completedAt' => $transaction->getCompletedAt()?->format('Y-m-d H:i:s'),
        ]);
    }
}
