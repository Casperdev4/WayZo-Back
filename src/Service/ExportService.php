<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\Chauffeur;
use App\Repository\TransactionRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twig\Environment;

/**
 * Service d'export des transactions pour la comptabilité
 * Génère des exports CSV et PDF
 */
class ExportService
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private Environment $twig
    ) {
    }

    /**
     * Récupère les transactions filtrées
     * 
     * @param Chauffeur $user L'utilisateur concerné
     * @param array $filters Filtres (dateFrom, dateTo, statut, type)
     * @return Transaction[]
     */
    public function getFilteredTransactions(Chauffeur $user, array $filters = []): array
    {
        return $this->transactionRepository->findByUserWithFilters(
            $user->getId(),
            $filters['dateFrom'] ?? null,
            $filters['dateTo'] ?? null,
            $filters['statut'] ?? null,
            $filters['type'] ?? null // 'sent' ou 'received'
        );
    }

    /**
     * Génère un export CSV des transactions
     */
    public function generateCSV(Chauffeur $user, array $filters = []): StreamedResponse
    {
        $transactions = $this->getFilteredTransactions($user, $filters);
        
        $response = new StreamedResponse(function() use ($transactions, $user) {
            $handle = fopen('php://output', 'w');
            
            // En-têtes CSV
            fputcsv($handle, [
                'Référence',
                'Date',
                'Montant (€)',
                'Type',
                'Statut',
                'Course - Départ',
                'Course - Arrivée',
                'Client',
                'Contrepartie',
                'Date de complétion'
            ], ';');
            
            // Données
            foreach ($transactions as $transaction) {
                $type = $this->getTransactionType($transaction, $user);
                $contrepartie = $this->getContrepartie($transaction, $user);
                $course = $transaction->getCourse();
                
                fputcsv($handle, [
                    $transaction->getReference() ?? 'N/A',
                    $transaction->getDate()?->format('d/m/Y H:i'),
                    number_format($transaction->getMontant(), 2, ',', ' '),
                    $type === 'sent' ? 'Paiement envoyé' : 'Paiement reçu',
                    $this->translateStatut($transaction->getStatut()),
                    $course?->getDepart() ?? 'N/A',
                    $course?->getArrivee() ?? 'N/A',
                    $course?->getNomClient() ?? 'N/A',
                    $contrepartie,
                    $transaction->getCompletedAt()?->format('d/m/Y H:i') ?? 'N/A'
                ], ';');
            }
            
            fclose($handle);
        });
        
        $filename = sprintf('transactions_%s_%s.csv', 
            $user->getId(), 
            (new \DateTime())->format('Y-m-d_H-i-s')
        );
        
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        // BOM UTF-8 pour Excel
        $response->headers->set('Content-Encoding', 'UTF-8');
        
        return $response;
    }

    /**
     * Génère un export PDF des transactions
     */
    public function generatePDF(Chauffeur $user, array $filters = []): Response
    {
        $transactions = $this->getFilteredTransactions($user, $filters);
        
        // Calculer les statistiques
        $stats = $this->calculateStats($transactions, $user);
        
        // Générer le HTML via Twig
        $html = $this->twig->render('exports/transactions.html.twig', [
            'user' => $user,
            'transactions' => $transactions,
            'filters' => $filters,
            'stats' => $stats,
            'generatedAt' => new \DateTime()
        ]);
        
        // Configuration DomPDF
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = sprintf('transactions_%s_%s.pdf', 
            $user->getId(), 
            (new \DateTime())->format('Y-m-d_H-i-s')
        );
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    /**
     * Génère un aperçu HTML (pour preview)
     */
    public function generatePreview(Chauffeur $user, array $filters = []): string
    {
        $transactions = $this->getFilteredTransactions($user, $filters);
        $stats = $this->calculateStats($transactions, $user);
        
        return $this->twig->render('exports/transactions.html.twig', [
            'user' => $user,
            'transactions' => $transactions,
            'filters' => $filters,
            'stats' => $stats,
            'generatedAt' => new \DateTime(),
            'isPreview' => true
        ]);
    }

    /**
     * Récupère les données JSON pour l'API
     */
    public function getTransactionsData(Chauffeur $user, array $filters = []): array
    {
        $transactions = $this->getFilteredTransactions($user, $filters);
        $stats = $this->calculateStats($transactions, $user);
        
        $data = [];
        foreach ($transactions as $transaction) {
            $type = $this->getTransactionType($transaction, $user);
            $course = $transaction->getCourse();
            
            $data[] = [
                'id' => $transaction->getId(),
                'reference' => $transaction->getReference(),
                'date' => $transaction->getDate()?->format('c'),
                'montant' => $transaction->getMontant(),
                'type' => $type,
                'typeLabel' => $type === 'sent' ? 'Paiement envoyé' : 'Paiement reçu',
                'statut' => $transaction->getStatut(),
                'statutLabel' => $this->translateStatut($transaction->getStatut()),
                'contrepartie' => $this->getContrepartie($transaction, $user),
                'course' => $course ? [
                    'id' => $course->getId(),
                    'depart' => $course->getDepart(),
                    'arrivee' => $course->getArrivee(),
                    'client' => $course->getNomClient(),
                    'date' => $course->getDate()?->format('Y-m-d'),
                    'heure' => $course->getHeure()?->format('H:i')
                ] : null,
                'completedAt' => $transaction->getCompletedAt()?->format('c')
            ];
        }
        
        return [
            'transactions' => $data,
            'stats' => $stats,
            'filters' => $filters,
            'generatedAt' => (new \DateTime())->format('c')
        ];
    }

    /**
     * Calcule les statistiques des transactions
     */
    private function calculateStats(array $transactions, Chauffeur $user): array
    {
        $totalSent = 0;
        $totalReceived = 0;
        $countSent = 0;
        $countReceived = 0;
        $countByStatut = [
            'pending' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'refunded' => 0
        ];
        
        foreach ($transactions as $transaction) {
            $type = $this->getTransactionType($transaction, $user);
            $montant = $transaction->getMontant();
            $statut = $transaction->getStatut();
            
            if ($type === 'sent') {
                $totalSent += $montant;
                $countSent++;
            } else {
                $totalReceived += $montant;
                $countReceived++;
            }
            
            if (isset($countByStatut[$statut])) {
                $countByStatut[$statut]++;
            }
        }
        
        return [
            'totalTransactions' => count($transactions),
            'totalSent' => $totalSent,
            'totalReceived' => $totalReceived,
            'countSent' => $countSent,
            'countReceived' => $countReceived,
            'balance' => $totalReceived - $totalSent,
            'countByStatut' => $countByStatut
        ];
    }

    /**
     * Détermine le type de transaction (envoyé ou reçu)
     */
    private function getTransactionType(Transaction $transaction, Chauffeur $user): string
    {
        if ($transaction->getChauffeurPayeur()?->getId() === $user->getId()) {
            return 'sent';
        }
        return 'received';
    }

    /**
     * Récupère le nom de la contrepartie
     */
    private function getContrepartie(Transaction $transaction, Chauffeur $user): string
    {
        $type = $this->getTransactionType($transaction, $user);
        
        if ($type === 'sent') {
            $contrepartie = $transaction->getChauffeurReceveur();
        } else {
            $contrepartie = $transaction->getChauffeurPayeur();
        }
        
        if (!$contrepartie) {
            return 'N/A';
        }
        
        return $contrepartie->getPrenom() . ' ' . $contrepartie->getNom();
    }

    /**
     * Traduit le statut en français
     */
    private function translateStatut(?string $statut): string
    {
        return match ($statut) {
            'pending' => 'En attente',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            'refunded' => 'Remboursé',
            default => 'Inconnu'
        };
    }
}
