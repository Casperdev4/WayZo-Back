<?php

namespace App\Command;

use App\Repository\RideRepository;
use App\Service\GeocodingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:geocode-rides',
    description: 'Géocode les adresses des courses existantes pour obtenir les coordonnées GPS'
)]
class GeocodeRidesCommand extends Command
{
    public function __construct(
        private RideRepository $rideRepository,
        private GeocodingService $geocodingService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Récupérer toutes les courses sans coordonnées
        $rides = $this->rideRepository->createQueryBuilder('r')
            ->where('r.departLat IS NULL OR r.destinationLat IS NULL')
            ->getQuery()
            ->getResult();
        
        $count = count($rides);
        $io->info("Traitement de $count courses...");
        
        $success = 0;
        $errors = 0;
        
        foreach ($rides as $ride) {
            $io->text("Course #{$ride->getId()}: {$ride->getDepart()} → {$ride->getDestination()}");
            
            $coords = $this->geocodingService->geocodeRide($ride->getDepart(), $ride->getDestination());
            
            if ($coords['departure']) {
                $ride->setDepartLat($coords['departure']['lat']);
                $ride->setDepartLng($coords['departure']['lng']);
                $io->text("  ✓ Départ: {$coords['departure']['lat']}, {$coords['departure']['lng']}");
            } else {
                $io->warning("  ✗ Départ non géocodé");
                $errors++;
            }
            
            if ($coords['arrival']) {
                $ride->setDestinationLat($coords['arrival']['lat']);
                $ride->setDestinationLng($coords['arrival']['lng']);
                $io->text("  ✓ Destination: {$coords['arrival']['lat']}, {$coords['arrival']['lng']}");
            } else {
                $io->warning("  ✗ Destination non géocodée");
                $errors++;
            }
            
            if ($coords['departure'] || $coords['arrival']) {
                $success++;
            }
            
            // Pause pour respecter les limites de l'API Nominatim (1 req/sec)
            usleep(1100000); // 1.1 seconde
        }
        
        $this->entityManager->flush();
        
        $io->success("Terminé ! $success courses mises à jour, $errors erreurs.");
        
        return Command::SUCCESS;
    }
}
