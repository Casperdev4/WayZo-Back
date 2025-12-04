<?php

namespace App\Command;

use App\Entity\Chauffeur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Create a test user for login',
)]
class CreateTestUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(Chauffeur::class)
            ->findOneBy(['email' => 'admin-01@ecme.com']);

        if ($existingUser) {
            // Mettre à jour le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($existingUser, '123Qwe');
            $existingUser->setPassword($hashedPassword);
            $this->entityManager->flush();

            $io->success('User password updated!');
            $io->text('Email: admin-01@ecme.com');
            $io->text('Password: 123Qwe');
            return Command::SUCCESS;
        }

        // Créer un nouveau chauffeur
        $chauffeur = new Chauffeur();
        $chauffeur->setNom('Test');
        $chauffeur->setPrenom('Admin');
        $chauffeur->setEmail('admin-01@ecme.com');
        $chauffeur->setTel('0123456789');
        $chauffeur->setSiret('12345678901234');
        $chauffeur->setNomSociete('WayZo Test');
        $chauffeur->setAdresse('123 Rue Test');
        $chauffeur->setVille('Paris');
        $chauffeur->setCodePostal('75001');
        $chauffeur->setKbis('test-kbis.pdf');
        $chauffeur->setCarteVtc('test-vtc.pdf');
        $chauffeur->setVehicle('Berline');
        $chauffeur->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $chauffeur->setDateNaissance(new \DateTimeImmutable('1990-01-01'));

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($chauffeur, '123Qwe');
        $chauffeur->setPassword($hashedPassword);

        $this->entityManager->persist($chauffeur);
        $this->entityManager->flush();

        $io->success('Test user created successfully!');
        $io->text('Email: admin-01@ecme.com');
        $io->text('Password: 123Qwe');

        return Command::SUCCESS;
    }
}
