<?php

namespace App\Service;

use App\Entity\Facture;
use App\Entity\Course;
use App\Entity\Chauffeur;
use App\Entity\Transaction;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

/**
 * Service de gestion de la facturation VTC
 * 
 * Gère la création automatique des factures lors de la finalisation des courses
 * et la génération des PDFs
 */
class FacturationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private FactureRepository $factureRepository,
        private ParameterBagInterface $params,
        private Environment $twig
    ) {}

    /**
     * Crée les factures automatiquement quand une course est terminée
     * 
     * @param Course $course La course terminée
     * @param Transaction $transaction La transaction associée
     * @return array Les factures créées [prestation, sous_traitance]
     */
    public function createFacturesForCourse(Course $course, Transaction $transaction): array
    {
        $vendeur = $course->getChauffeurVendeur();
        $accepteur = $course->getChauffeurAccepteur();
        
        if (!$vendeur || !$accepteur) {
            throw new \InvalidArgumentException('La course doit avoir un vendeur et un accepteur');
        }

        $factures = [];

        // 1. Facture de PRESTATION : L'accepteur facture au vendeur
        // (L'accepteur a effectué la course, il facture sa prestation)
        $facturePrestation = $this->createFacture(
            type: Facture::TYPE_PRESTATION,
            emetteur: $accepteur,
            destinataire: $vendeur,
            course: $course,
            transaction: $transaction,
            description: sprintf(
                "Prestation de transport VTC - Course du %s\nTrajet : %s → %s\nClient : %s",
                $course->getDate()->format('d/m/Y'),
                $course->getDepart(),
                $course->getArrivee(),
                $course->getNomClient()
            )
        );
        $factures['prestation'] = $facturePrestation;

        // 2. Facture de SOUS-TRAITANCE : Le vendeur facture à l'accepteur
        // (Le vendeur a sous-traité la course, il facture la mise en relation)
        // Note: Cette facture est optionnelle, dépend du modèle économique
        // Pour l'instant on la crée avec 0€ (ou commission si définie)
        
        $this->em->flush();

        // Générer les PDFs
        $this->generatePDF($facturePrestation);

        return $factures;
    }

    /**
     * Crée une facture
     */
    public function createFacture(
        string $type,
        Chauffeur $emetteur,
        Chauffeur $destinataire,
        Course $course,
        ?Transaction $transaction = null,
        ?string $description = null
    ): Facture {
        // Compter les factures existantes pour générer le numéro
        $count = $this->factureRepository->countByYear((int) date('Y'));
        
        $facture = new Facture();
        $facture->setNumero(Facture::generateNumero($count));
        $facture->setType($type);
        $facture->setEmetteur($emetteur);
        $facture->setDestinataire($destinataire);
        $facture->setCourse($course);
        $facture->setTransaction($transaction);
        $facture->setDescription($description);
        
        // Montant = prix de la course
        $montantHT = floatval($course->getPrix());
        $facture->setMontantHT(number_format($montantHT, 2, '.', ''));
        
        // Date d'échéance = 30 jours
        $echeance = new \DateTime();
        $echeance->modify('+30 days');
        $facture->setDateEcheance($echeance);

        // Snapshot des informations société
        $facture->setEmetteurInfo($this->getChauffeurInfo($emetteur));
        $facture->setDestinataireInfo($this->getChauffeurInfo($destinataire));
        $facture->setCourseDetails($this->getCourseDetails($course));

        // Émettre directement la facture
        $facture->issue();

        $this->em->persist($facture);
        
        return $facture;
    }

    /**
     * Génère le PDF d'une facture avec DomPDF
     */
    public function generatePDF(Facture $facture): string
    {
        $projectDir = $this->params->get('kernel.project_dir');
        $uploadDir = $projectDir . '/public/uploads/factures';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = sprintf('facture_%s.pdf', $facture->getNumero());
        $filepath = $uploadDir . '/' . $filename;

        // Générer le HTML de la facture
        $html = $this->twig->render('facture/pdf.html.twig', [
            'facture' => $facture,
        ]);

        // Générer le PDF avec DomPDF
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'sans-serif');
        
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Sauvegarder le PDF
        file_put_contents($filepath, $dompdf->output());

        // Sauvegarder le chemin
        $facture->setPdfPath('/uploads/factures/' . $filename);
        $this->em->flush();

        return $facture->getPdfPath();
    }

    /**
     * Récupère les informations d'un chauffeur pour la facture
     */
    private function getChauffeurInfo(Chauffeur $chauffeur): array
    {
        return [
            'id' => $chauffeur->getId(),
            'nom' => $chauffeur->getNom(),
            'prenom' => $chauffeur->getPrenom(),
            'nomComplet' => $chauffeur->getPrenom() . ' ' . $chauffeur->getNom(),
            'email' => $chauffeur->getEmail(),
            'telephone' => $chauffeur->getTel() ?? '',
            'adresse' => $chauffeur->getAdresse() ?? '',
            'siret' => $chauffeur->getSiret() ?? '',
            'raisonSociale' => $chauffeur->getNomSociete() ?? $chauffeur->getNom(),
        ];
    }

    /**
     * Récupère les détails de la course pour la facture
     */
    private function getCourseDetails(Course $course): array
    {
        return [
            'id' => $course->getId(),
            'date' => $course->getDate()->format('d/m/Y'),
            'heure' => $course->getHeure()->format('H:i'),
            'depart' => $course->getDepart(),
            'arrivee' => $course->getArrivee(),
            'nomClient' => $course->getNomClient(),
            'prix' => $course->getPrix(),
            'vehicule' => $course->getVehicule(),
            'passagers' => $course->getPassagers(),
        ];
    }

    /**
     * Marque une facture comme payée
     */
    public function markAsPaid(Facture $facture): void
    {
        $facture->markAsPaid();
        $this->em->flush();
    }

    /**
     * Récupère les statistiques de facturation pour un chauffeur
     */
    public function getStats(Chauffeur $chauffeur): array
    {
        return $this->factureRepository->getStats($chauffeur);
    }
}
