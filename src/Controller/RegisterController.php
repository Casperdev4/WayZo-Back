<?php

namespace App\Controller;

use App\Entity\Chauffeur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginAuthenticator;

final class RegisterController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        LoginAuthenticator $authenticator
    ): Response {
        if ($request->isMethod('POST')) {
            $chauffeur = new Chauffeur();

            // Remplissage des champs
            $chauffeur->setNom($request->request->get('lastName'));
            $chauffeur->setPrenom($request->request->get('firstName'));
            $chauffeur->setEmail($request->request->get('email'));
            $chauffeur->setTel($request->request->get('phone'));
            $chauffeur->setSiret($request->request->get('siret'));
            $chauffeur->setAdresse($request->request->get('address'));
            $chauffeur->setVille($request->request->get('city'));
            $chauffeur->setCodePostal($request->request->get('postalCode'));
            $chauffeur->setVehicle($request->request->get('vehicleSelect'));
            $chauffeur->setDateNaissance(new \DateTimeImmutable($request->request->get('dob')));
            $chauffeur->setNomSociete($request->request->get('companyName'));
            $chauffeur->setRoles(['ROLE_USER']);

            // Hash password
            if ($request->request->get('password') !== $request->request->get('confirmPassword')) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_register');
            }

            $hashedPassword = $passwordHasher->hashPassword($chauffeur, $request->request->get('password'));
            $chauffeur->setPassword($hashedPassword);

            // Uploads
            $uploadDir = $this->getParameter('uploads_directory');

            $this->handleFileUpload($request, $chauffeur, 'permis', $uploadDir . '/permis');
            $this->handleFileUpload($request, $chauffeur, 'kbis', $uploadDir . '/kbis');
            $this->handleFileUpload($request, $chauffeur, 'vtcCard', $uploadDir . '/vtc', 'setCarteVtc');
            $this->handleFileUpload($request, $chauffeur, 'macaron', $uploadDir . '/macaron');
            $this->handleFileUpload($request, $chauffeur, 'identityCardPhoto', $uploadDir . '/identite', 'setPieceIdentite');

            $entityManager->persist($chauffeur);
            $entityManager->flush();

            // ✅ Connexion automatique
            return $userAuthenticator->authenticateUser(
                $chauffeur,
                $authenticator,
                $request
            );
        }

        return $this->render('security/register.html.twig');
    }

    private function handleFileUpload(Request $request, Chauffeur $chauffeur, string $field, string $destination, ?string $setter = null): void
    {
        $setter ??= 'set' . ucfirst($field);
        $file = $request->files->get($field);

        if ($file) {
            $filename = uniqid() . '.' . $file->guessExtension();
            $file->move($destination, $filename);
            $chauffeur->$setter($filename);
        }
    }
}
