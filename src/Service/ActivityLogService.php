<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Chauffeur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack
    ) {}

    /**
     * Enregistrer une activité
     */
    public function log(
        Chauffeur $chauffeur,
        string $type,
        ?string $description = null,
        ?array $metadata = null
    ): ActivityLog {
        $request = $this->requestStack->getCurrentRequest();
        
        $activity = new ActivityLog();
        $activity->setChauffeur($chauffeur);
        $activity->setType($type);
        $activity->setDescription($description);
        $activity->setMetadata($metadata);
        
        if ($request) {
            $activity->setIpAddress($request->getClientIp());
            $activity->setUserAgent($request->headers->get('User-Agent'));
        }
        
        $this->em->persist($activity);
        $this->em->flush();
        
        return $activity;
    }

    /**
     * Log de connexion
     */
    public function logLogin(Chauffeur $chauffeur): ActivityLog
    {
        return $this->log(
            $chauffeur,
            ActivityLog::TYPE_LOGIN,
            'Connexion réussie',
            ['email' => $chauffeur->getEmail()]
        );
    }

    /**
     * Log de déconnexion
     */
    public function logLogout(Chauffeur $chauffeur): ActivityLog
    {
        return $this->log(
            $chauffeur,
            ActivityLog::TYPE_LOGOUT,
            'Déconnexion'
        );
    }

    /**
     * Log de création de course
     */
    public function logRideCreated(Chauffeur $chauffeur, int $rideId, string $depart, string $destination): ActivityLog
    {
        return $this->log(
            $chauffeur,
            ActivityLog::TYPE_RIDE_CREATED,
            "Course créée : $depart → $destination",
            ['rideId' => $rideId, 'depart' => $depart, 'destination' => $destination]
        );
    }

    /**
     * Log d'acceptation de course
     */
    public function logRideAccepted(Chauffeur $chauffeur, int $rideId, string $depart, string $destination): ActivityLog
    {
        return $this->log(
            $chauffeur,
            ActivityLog::TYPE_RIDE_ACCEPTED,
            "Course acceptée : $depart → $destination",
            ['rideId' => $rideId]
        );
    }

    /**
     * Log de course terminée
     */
    public function logRideCompleted(Chauffeur $chauffeur, int $rideId, float $price): ActivityLog
    {
        return $this->log(
            $chauffeur,
            ActivityLog::TYPE_RIDE_COMPLETED,
            "Course terminée - {$price}€",
            ['rideId' => $rideId, 'price' => $price]
        );
    }

    /**
     * Log d'annulation de course
     */
    public function logRideCancelled(Chauffeur $chauffeur, int $rideId, ?string $reason = null): ActivityLog
    {
        return $this->log(
            $chauffeur,
            ActivityLog::TYPE_RIDE_CANCELLED,
            'Course annulée' . ($reason ? " : $reason" : ''),
            ['rideId' => $rideId, 'reason' => $reason]
        );
    }

    /**
     * Log d'envoi de message
     */
    public function logMessageSent(Chauffeur $chauffeur, int $courseId, int $recipientId): ActivityLog
    {
        return $this->log(
            $chauffeur,
            ActivityLog::TYPE_MESSAGE_SENT,
            'Message envoyé',
            ['courseId' => $courseId, 'recipientId' => $recipientId]
        );
    }

    /**
     * Log de mise à jour du profil
     */
    public function logProfileUpdated(Chauffeur $chauffeur, array $updatedFields): ActivityLog
    {
        return $this->log(
            $chauffeur,
            ActivityLog::TYPE_PROFILE_UPDATED,
            'Profil mis à jour',
            ['fields' => $updatedFields]
        );
    }

    /**
     * Log de changement de mot de passe
     */
    public function logPasswordChanged(Chauffeur $chauffeur): ActivityLog
    {
        return $this->log(
            $chauffeur,
            ActivityLog::TYPE_PASSWORD_CHANGED,
            'Mot de passe modifié'
        );
    }

    /**
     * Log d'upload de document
     */
    public function logDocumentUploaded(Chauffeur $chauffeur, string $documentType): ActivityLog
    {
        return $this->log(
            $chauffeur,
            ActivityLog::TYPE_DOCUMENT_UPLOADED,
            "Document uploadé : $documentType",
            ['documentType' => $documentType]
        );
    }
}
