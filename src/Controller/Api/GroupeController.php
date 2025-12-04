<?php

namespace App\Controller\Api;

use App\Entity\Groupe;
use App\Entity\GroupeMembre;
use App\Entity\GroupeInvitation;
use App\Entity\Chauffeur;
use App\Repository\GroupeRepository;
use App\Repository\GroupeMembreRepository;
use App\Repository\GroupeInvitationRepository;
use App\Repository\ChauffeurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/groupes')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GroupeController extends AbstractController
{
    public function __construct(
        private GroupeRepository $groupeRepository,
        private GroupeMembreRepository $membreRepository,
        private GroupeInvitationRepository $invitationRepository,
        private ChauffeurRepository $chauffeurRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Liste tous les groupes de l'utilisateur (propriétaire ou membre)
     */
    #[Route('', name: 'api_groupes_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupes = $this->groupeRepository->findAllForChauffeur($user);
        
        $result = [];
        foreach ($groupes as $groupe) {
            $data = $groupe->toArray();
            $data['isOwner'] = $groupe->getProprietaire()->getId() === $user->getId();
            $result[] = $data;
        }

        return $this->json($result);
    }

    /**
     * Récupère les détails d'un groupe
     */
    #[Route('/{id}', name: 'api_groupes_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->find($id);
        
        if (!$groupe) {
            return $this->json(['error' => 'Groupe non trouvé'], 404);
        }

        // Vérifier l'accès
        if (!$groupe->hasMembre($user)) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $data = $groupe->toArray();
        $data['isOwner'] = $groupe->getProprietaire()->getId() === $user->getId();
        
        // Ajouter les invitations en attente si propriétaire
        if ($data['isOwner']) {
            $invitations = $this->invitationRepository->findByGroupe($groupe, GroupeInvitation::STATUS_PENDING);
            $data['pendingInvitations'] = array_map(fn($i) => $i->toArray(), $invitations);
        }

        return $this->json($data);
    }

    /**
     * Crée un nouveau groupe
     */
    #[Route('', name: 'api_groupes_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);

        if (empty($data['nom'])) {
            return $this->json(['error' => 'Le nom du groupe est requis'], 400);
        }

        // Limiter le nombre de groupes par utilisateur (ex: max 5)
        $count = $this->groupeRepository->countByProprietaire($user);
        if ($count >= 5) {
            return $this->json(['error' => 'Vous avez atteint la limite de 5 groupes'], 400);
        }

        $groupe = new Groupe();
        $groupe->setNom($data['nom']);
        $groupe->setDescription($data['description'] ?? null);
        $groupe->setProprietaire($user);

        $this->groupeRepository->save($groupe, true);

        return $this->json($groupe->toArray(), 201);
    }

    /**
     * Met à jour un groupe
     */
    #[Route('/{id}', name: 'api_groupes_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->find($id);
        
        if (!$groupe) {
            return $this->json(['error' => 'Groupe non trouvé'], 404);
        }

        // Seul le propriétaire peut modifier
        if ($groupe->getProprietaire()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Seul le propriétaire peut modifier le groupe'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $groupe->setNom($data['nom']);
        }
        if (isset($data['description'])) {
            $groupe->setDescription($data['description']);
        }
        
        $groupe->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->json($groupe->toArray());
    }

    /**
     * Supprime un groupe
     */
    #[Route('/{id}', name: 'api_groupes_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->find($id);
        
        if (!$groupe) {
            return $this->json(['error' => 'Groupe non trouvé'], 404);
        }

        if ($groupe->getProprietaire()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Seul le propriétaire peut supprimer le groupe'], 403);
        }

        $this->groupeRepository->remove($groupe, true);

        return $this->json(['message' => 'Groupe supprimé avec succès']);
    }

    /**
     * Régénère le code d'invitation du groupe
     */
    #[Route('/{id}/regenerate-code', name: 'api_groupes_regenerate_code', methods: ['POST'])]
    public function regenerateCode(int $id): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->find($id);
        
        if (!$groupe) {
            return $this->json(['error' => 'Groupe non trouvé'], 404);
        }

        if ($groupe->getProprietaire()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Seul le propriétaire peut régénérer le code'], 403);
        }

        $groupe->regenerateCode();
        $this->em->flush();

        return $this->json(['code' => $groupe->getCode()]);
    }

    /**
     * Rejoint un groupe via son code
     */
    #[Route('/join/{code}', name: 'api_groupes_join', methods: ['POST'])]
    public function joinByCode(string $code): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->findByCode($code);
        
        if (!$groupe) {
            return $this->json(['error' => 'Code de groupe invalide'], 404);
        }

        // Vérifier si déjà membre
        if ($groupe->hasMembre($user)) {
            return $this->json(['error' => 'Vous êtes déjà membre de ce groupe'], 400);
        }

        // Ajouter comme membre
        $membre = new GroupeMembre();
        $membre->setGroupe($groupe);
        $membre->setChauffeur($user);
        $membre->setRole(GroupeMembre::ROLE_MEMBRE);

        $this->membreRepository->save($membre, true);

        return $this->json([
            'message' => 'Vous avez rejoint le groupe avec succès',
            'groupe' => $groupe->toArray(),
        ]);
    }

    /**
     * Invite un chauffeur dans le groupe
     */
    #[Route('/{id}/invite', name: 'api_groupes_invite', methods: ['POST'])]
    public function invite(int $id, Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->find($id);
        
        if (!$groupe) {
            return $this->json(['error' => 'Groupe non trouvé'], 404);
        }

        // Vérifier si l'utilisateur peut inviter (propriétaire ou admin)
        $isOwner = $groupe->getProprietaire()->getId() === $user->getId();
        $membre = $this->membreRepository->findOneMembre($groupe, $user);
        $isAdmin = $membre && $membre->isAdmin();

        if (!$isOwner && !$isAdmin) {
            return $this->json(['error' => 'Vous n\'avez pas le droit d\'inviter dans ce groupe'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $chauffeurId = $data['chauffeurId'] ?? null;
        $email = $data['email'] ?? null;
        $message = $data['message'] ?? null;

        if (!$chauffeurId && !$email) {
            return $this->json(['error' => 'chauffeurId ou email requis'], 400);
        }

        $chauffeurInvite = null;
        if ($chauffeurId) {
            $chauffeurInvite = $this->chauffeurRepository->find($chauffeurId);
            if (!$chauffeurInvite) {
                return $this->json(['error' => 'Chauffeur non trouvé'], 404);
            }
            $email = $chauffeurInvite->getEmail();
        }

        // Vérifier si déjà membre
        if ($chauffeurInvite && $groupe->hasMembre($chauffeurInvite)) {
            return $this->json(['error' => 'Ce chauffeur est déjà membre du groupe'], 400);
        }

        // Vérifier si invitation en attente existe
        if ($this->invitationRepository->hasPendingInvitation($groupe, $chauffeurInvite, $email)) {
            return $this->json(['error' => 'Une invitation est déjà en attente pour ce chauffeur'], 400);
        }

        // Créer l'invitation
        $invitation = new GroupeInvitation();
        $invitation->setGroupe($groupe);
        $invitation->setInvitePar($user);
        $invitation->setChauffeurInvite($chauffeurInvite);
        $invitation->setEmail($email);
        $invitation->setMessage($message);

        $this->invitationRepository->save($invitation, true);

        // TODO: Envoyer un email d'invitation

        return $this->json([
            'message' => 'Invitation envoyée avec succès',
            'invitation' => $invitation->toArray(),
        ], 201);
    }

    /**
     * Liste les invitations en attente pour l'utilisateur courant
     */
    #[Route('/invitations/pending', name: 'api_groupes_invitations_pending', methods: ['GET'])]
    public function pendingInvitations(): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        // Chercher par chauffeur
        $invitations = $this->invitationRepository->findPendingForChauffeur($user);
        
        // Chercher aussi par email
        $emailInvitations = $this->invitationRepository->findPendingByEmail($user->getEmail());
        
        // Fusionner sans doublons
        $allInvitations = array_merge($invitations, $emailInvitations);
        $uniqueInvitations = [];
        $ids = [];
        foreach ($allInvitations as $inv) {
            if (!in_array($inv->getId(), $ids)) {
                $ids[] = $inv->getId();
                $uniqueInvitations[] = $inv->toArray();
            }
        }

        return $this->json($uniqueInvitations);
    }

    /**
     * Répond à une invitation
     */
    #[Route('/invitations/{token}/respond', name: 'api_groupes_invitation_respond', methods: ['POST'])]
    public function respondToInvitation(string $token, Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $invitation = $this->invitationRepository->findByToken($token);
        
        if (!$invitation) {
            return $this->json(['error' => 'Invitation non trouvée'], 404);
        }

        if (!$invitation->isPending()) {
            return $this->json(['error' => 'Cette invitation a déjà été traitée'], 400);
        }

        if ($invitation->isExpired()) {
            $invitation->setStatus(GroupeInvitation::STATUS_EXPIRED);
            $this->em->flush();
            return $this->json(['error' => 'Cette invitation a expiré'], 400);
        }

        // Vérifier que l'invitation concerne l'utilisateur
        $isForUser = ($invitation->getChauffeurInvite() && $invitation->getChauffeurInvite()->getId() === $user->getId())
                  || ($invitation->getEmail() && strtolower($invitation->getEmail()) === strtolower($user->getEmail()));

        if (!$isForUser) {
            return $this->json(['error' => 'Cette invitation ne vous concerne pas'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $accept = $data['accept'] ?? false;

        if ($accept) {
            $invitation->accept();
            
            // Ajouter comme membre du groupe
            $groupe = $invitation->getGroupe();
            
            if (!$groupe->hasMembre($user)) {
                $membre = new GroupeMembre();
                $membre->setGroupe($groupe);
                $membre->setChauffeur($user);
                $membre->setRole(GroupeMembre::ROLE_MEMBRE);
                $membre->setInvitePar($invitation->getInvitePar());
                
                $this->membreRepository->save($membre);
            }

            $this->em->flush();

            return $this->json([
                'message' => 'Invitation acceptée, vous êtes maintenant membre du groupe',
                'groupe' => $groupe->toArray(),
            ]);
        } else {
            $invitation->reject();
            $this->em->flush();

            return $this->json(['message' => 'Invitation refusée']);
        }
    }

    /**
     * Quitte un groupe
     */
    #[Route('/{id}/leave', name: 'api_groupes_leave', methods: ['POST'])]
    public function leave(int $id): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->find($id);
        
        if (!$groupe) {
            return $this->json(['error' => 'Groupe non trouvé'], 404);
        }

        // Le propriétaire ne peut pas quitter son propre groupe
        if ($groupe->getProprietaire()->getId() === $user->getId()) {
            return $this->json(['error' => 'Le propriétaire ne peut pas quitter son groupe. Supprimez-le à la place.'], 400);
        }

        $membre = $this->membreRepository->findOneMembre($groupe, $user);
        
        if (!$membre) {
            return $this->json(['error' => 'Vous n\'êtes pas membre de ce groupe'], 400);
        }

        $this->membreRepository->remove($membre, true);

        return $this->json(['message' => 'Vous avez quitté le groupe']);
    }

    /**
     * Retire un membre du groupe
     */
    #[Route('/{id}/members/{membreId}', name: 'api_groupes_remove_member', methods: ['DELETE'])]
    public function removeMember(int $id, int $membreId): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->find($id);
        
        if (!$groupe) {
            return $this->json(['error' => 'Groupe non trouvé'], 404);
        }

        // Seul le propriétaire peut retirer des membres
        if ($groupe->getProprietaire()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Seul le propriétaire peut retirer des membres'], 403);
        }

        $membre = $this->membreRepository->find($membreId);
        
        if (!$membre || $membre->getGroupe()->getId() !== $groupe->getId()) {
            return $this->json(['error' => 'Membre non trouvé'], 404);
        }

        $this->membreRepository->remove($membre, true);

        return $this->json(['message' => 'Membre retiré du groupe']);
    }

    /**
     * Modifie le rôle d'un membre
     */
    #[Route('/{id}/members/{membreId}/role', name: 'api_groupes_update_member_role', methods: ['PUT'])]
    public function updateMemberRole(int $id, int $membreId, Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->find($id);
        
        if (!$groupe) {
            return $this->json(['error' => 'Groupe non trouvé'], 404);
        }

        if ($groupe->getProprietaire()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Seul le propriétaire peut modifier les rôles'], 403);
        }

        $membre = $this->membreRepository->find($membreId);
        
        if (!$membre || $membre->getGroupe()->getId() !== $groupe->getId()) {
            return $this->json(['error' => 'Membre non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $role = $data['role'] ?? null;

        if (!in_array($role, [GroupeMembre::ROLE_MEMBRE, GroupeMembre::ROLE_ADMIN])) {
            return $this->json(['error' => 'Rôle invalide'], 400);
        }

        $membre->setRole($role);
        $this->em->flush();

        return $this->json($membre->toArray());
    }

    /**
     * Annule une invitation
     */
    #[Route('/{id}/invitations/{invitationId}', name: 'api_groupes_cancel_invitation', methods: ['DELETE'])]
    public function cancelInvitation(int $id, int $invitationId): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->find($id);
        
        if (!$groupe) {
            return $this->json(['error' => 'Groupe non trouvé'], 404);
        }

        if ($groupe->getProprietaire()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Seul le propriétaire peut annuler les invitations'], 403);
        }

        $invitation = $this->invitationRepository->find($invitationId);
        
        if (!$invitation || $invitation->getGroupe()->getId() !== $groupe->getId()) {
            return $this->json(['error' => 'Invitation non trouvée'], 404);
        }

        $this->invitationRepository->remove($invitation, true);

        return $this->json(['message' => 'Invitation annulée']);
    }

    /**
     * Recherche des chauffeurs à inviter
     */
    #[Route('/{id}/search-chauffeurs', name: 'api_groupes_search_chauffeurs', methods: ['GET'])]
    public function searchChauffeurs(int $id, Request $request): JsonResponse
    {
        /** @var Chauffeur $user */
        $user = $this->getUser();
        
        $groupe = $this->groupeRepository->find($id);
        
        if (!$groupe) {
            return $this->json(['error' => 'Groupe non trouvé'], 404);
        }

        if (!$groupe->hasMembre($user)) {
            return $this->json(['error' => 'Accès non autorisé'], 403);
        }

        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return $this->json([]);
        }

        // Chercher les chauffeurs correspondant à la requête
        $chauffeurs = $this->chauffeurRepository->createQueryBuilder('c')
            ->andWhere('c.nom LIKE :query OR c.prenom LIKE :query OR c.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($chauffeurs as $chauffeur) {
            // Exclure les membres existants
            if (!$groupe->hasMembre($chauffeur)) {
                $result[] = [
                    'id' => $chauffeur->getId(),
                    'name' => $chauffeur->getFullName(),
                    'email' => $chauffeur->getEmail(),
                    'company' => $chauffeur->getNomSociete(),
                    'img' => '/img/avatars/' . ($chauffeur->getId() % 10 + 1) . '.jpg',
                ];
            }
        }

        return $this->json($result);
    }
}
