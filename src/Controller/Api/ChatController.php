<?php

namespace App\Controller\Api;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\ChauffeurRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ChatController extends BaseApiController
{
    public function __construct(
        private MessageRepository $messageRepository,
        private ChauffeurRepository $chauffeurRepository,
        private CourseRepository $courseRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Liste des conversations (chauffeurs avec qui on a échangé)
     */
    #[Route('/chats', name: 'api_chats_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getChats(): JsonResponse
    {
        $user = $this->getChauffeur();
        
        // Récupérer tous les messages envoyés ou reçus par l'utilisateur
        $sentMessages = $this->messageRepository->findBy(['expediteur' => $user]);
        
        // Grouper par conversation (par course ou par destinataire)
        $conversations = [];
        $processedCourses = [];
        
        foreach ($sentMessages as $message) {
            $course = $message->getCourse();
            if ($course && !in_array($course->getId(), $processedCourses)) {
                $processedCourses[] = $course->getId();
                
                // Trouver l'autre chauffeur dans la conversation
                $otherChauffeur = null;
                if ($course->getChauffeurVendeur() && $course->getChauffeurVendeur()->getId() !== $user->getId()) {
                    $otherChauffeur = $course->getChauffeurVendeur();
                } elseif ($course->getChauffeurAccepteur() && $course->getChauffeurAccepteur()->getId() !== $user->getId()) {
                    $otherChauffeur = $course->getChauffeurAccepteur();
                }
                
                if ($otherChauffeur) {
                    // Dernier message de cette conversation
                    $lastMessage = $this->messageRepository->findOneBy(
                        ['course' => $course],
                        ['dateEnvoi' => 'DESC']
                    );
                    
                    $conversations[] = [
                        'id' => 'chat-' . $course->getId(),
                        'courseId' => $course->getId(),
                        'chatType' => 'personal',
                        'muted' => false,
                        'unread' => 0,
                        'user' => [
                            'id' => $otherChauffeur->getId(),
                            'name' => $otherChauffeur->getPrenom() . ' ' . $otherChauffeur->getNom(),
                            'avatarImageUrl' => '/img/avatars/thumb-' . ($otherChauffeur->getId() % 15 + 1) . '.jpg',
                        ],
                        'lastConversation' => $lastMessage ? $lastMessage->getContenu() : '',
                        'lastConversationTime' => $lastMessage ? $lastMessage->getDateEnvoi()->format('Y-m-d H:i:s') : null,
                        'course' => [
                            'id' => $course->getId(),
                            'depart' => $course->getDepart(),
                            'destination' => $course->getArrivee(),
                            'date' => $course->getDate()?->format('Y-m-d'),
                        ],
                    ];
                }
            }
        }
        
        // Trier par date du dernier message
        usort($conversations, function($a, $b) {
            return strtotime($b['lastConversationTime'] ?? '1970-01-01') - strtotime($a['lastConversationTime'] ?? '1970-01-01');
        });
        
        return new JsonResponse($conversations);
    }

    /**
     * Récupérer une conversation complète
     */
    #[Route('/conversation/{id}', name: 'api_conversation_get', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getConversation(string $id): JsonResponse
    {
        $user = $this->getChauffeur();
        
        // Extraire l'ID de la course depuis l'ID du chat (format: chat-123)
        $courseId = str_replace('chat-', '', $id);
        $course = $this->courseRepository->find($courseId);
        
        if (!$course) {
            return new JsonResponse(['error' => 'Conversation non trouvée'], 404);
        }
        
        // Vérifier que l'utilisateur participe à cette conversation
        $isParticipant = ($course->getChauffeurVendeur() && $course->getChauffeurVendeur()->getId() === $user->getId())
            || ($course->getChauffeurAccepteur() && $course->getChauffeurAccepteur()->getId() === $user->getId());
            
        if (!$isParticipant) {
            return new JsonResponse(['error' => 'Accès refusé'], 403);
        }
        
        // Récupérer tous les messages de cette conversation
        $messages = $this->messageRepository->findBy(
            ['course' => $course],
            ['dateEnvoi' => 'ASC']
        );
        
        $conversation = array_map(function($message) use ($user) {
            $sender = $message->getExpediteur();
            return [
                'id' => 'msg-' . $message->getId(),
                'sender' => [
                    'id' => $sender->getId(),
                    'name' => $sender->getPrenom() . ' ' . $sender->getNom(),
                    'avatarImageUrl' => '/img/avatars/thumb-' . ($sender->getId() % 15 + 1) . '.jpg',
                ],
                'content' => $message->getContenu(),
                'timestamp' => $message->getDateEnvoi()->format('c'),
                'type' => 'regular',
                'isMyMessage' => $sender->getId() === $user->getId(),
            ];
        }, $messages);
        
        return new JsonResponse($conversation);
    }

    /**
     * Envoyer un message
     */
    #[Route('/conversation/{id}/message', name: 'api_conversation_send', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function sendMessage(string $id, Request $request): JsonResponse
    {
        $user = $this->getChauffeur();
        $data = json_decode($request->getContent(), true);
        
        // Extraire l'ID de la course
        $courseId = str_replace('chat-', '', $id);
        $course = $this->courseRepository->find($courseId);
        
        if (!$course) {
            return new JsonResponse(['error' => 'Conversation non trouvée'], 404);
        }
        
        // Créer le message
        $message = new Message();
        $message->setContenu($data['content'] ?? '');
        $message->setDateEnvoi(new \DateTime());
        $message->setExpediteur($user);
        $message->setCourse($course);
        
        $this->em->persist($message);
        $this->em->flush();
        
        return new JsonResponse([
            'id' => 'msg-' . $message->getId(),
            'sender' => [
                'id' => $user->getId(),
                'name' => $user->getPrenom() . ' ' . $user->getNom(),
                'avatarImageUrl' => '/img/avatars/thumb-' . ($user->getId() % 15 + 1) . '.jpg',
            ],
            'content' => $message->getContenu(),
            'timestamp' => $message->getDateEnvoi()->format('c'),
            'type' => 'regular',
            'isMyMessage' => true,
        ], 201);
    }

    /**
     * Liste des contacts (tous les chauffeurs)
     */
    #[Route('/contacts', name: 'api_contacts_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getChatContacts(): JsonResponse
    {
        $user = $this->getChauffeur();
        $chauffeurs = $this->chauffeurRepository->findAll();
        
        $contacts = [];
        foreach ($chauffeurs as $chauffeur) {
            // Exclure l'utilisateur courant
            if ($chauffeur->getId() === $user->getId()) {
                continue;
            }
            
            $contacts[] = [
                'id' => $chauffeur->getId(),
                'name' => $chauffeur->getPrenom() . ' ' . $chauffeur->getNom(),
                'email' => $chauffeur->getEmail(),
                'img' => '/img/avatars/thumb-' . ($chauffeur->getId() % 15 + 1) . '.jpg',
            ];
        }
        
        return new JsonResponse($contacts);
    }

    /**
     * Détails d'un contact
     */
    #[Route('/contacts/{id}', name: 'api_contact_details', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getContactDetails(int $id): JsonResponse
    {
        $chauffeur = $this->chauffeurRepository->find($id);
        
        if (!$chauffeur) {
            return new JsonResponse(['error' => 'Contact non trouvé'], 404);
        }
        
        return new JsonResponse([
            'id' => $chauffeur->getId(),
            'name' => $chauffeur->getPrenom() . ' ' . $chauffeur->getNom(),
            'email' => $chauffeur->getEmail(),
            'phone' => $chauffeur->getTel(),
            'img' => '/img/avatars/thumb-' . ($chauffeur->getId() % 15 + 1) . '.jpg',
            'personalInfo' => [
                'company' => $chauffeur->getNomSociete(),
                'siret' => $chauffeur->getSiret(),
                'vehicle' => $chauffeur->getVehicle(),
            ],
        ]);
    }

    /**
     * Démarrer une nouvelle conversation (pour une course)
     */
    #[Route('/chats/new', name: 'api_chat_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function startNewChat(Request $request): JsonResponse
    {
        $user = $this->getChauffeur();
        $data = json_decode($request->getContent(), true);
        
        $courseId = $data['courseId'] ?? null;
        $recipientId = $data['recipientId'] ?? null;
        
        if (!$courseId) {
            return new JsonResponse(['error' => 'courseId requis'], 400);
        }
        
        $course = $this->courseRepository->find($courseId);
        if (!$course) {
            return new JsonResponse(['error' => 'Course non trouvée'], 404);
        }
        
        // Déterminer le destinataire
        $otherChauffeur = null;
        if ($course->getChauffeurVendeur() && $course->getChauffeurVendeur()->getId() !== $user->getId()) {
            $otherChauffeur = $course->getChauffeurVendeur();
        } elseif ($course->getChauffeurAccepteur() && $course->getChauffeurAccepteur()->getId() !== $user->getId()) {
            $otherChauffeur = $course->getChauffeurAccepteur();
        }
        
        if (!$otherChauffeur) {
            return new JsonResponse(['error' => 'Aucun destinataire trouvé'], 400);
        }
        
        return new JsonResponse([
            'id' => 'chat-' . $course->getId(),
            'courseId' => $course->getId(),
            'chatType' => 'personal',
            'user' => [
                'id' => $otherChauffeur->getId(),
                'name' => $otherChauffeur->getPrenom() . ' ' . $otherChauffeur->getNom(),
                'avatarImageUrl' => '/img/avatars/thumb-' . ($otherChauffeur->getId() % 15 + 1) . '.jpg',
            ],
        ], 201);
    }
}
