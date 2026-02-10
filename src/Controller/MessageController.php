<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;

final class MessageController extends AbstractController
{
    private EntityManagerInterface $em;
    private MessageRepository $messageRepo;
    private UserRepository $userRepo;

    public function __construct(EntityManagerInterface $em, MessageRepository $messageRepo, UserRepository $userRepo)
    {
        $this->em = $em;
        $this->messageRepo = $messageRepo;
        $this->userRepo = $userRepo;
    }

    // 💌 Envoyer un message
    #[Route('/api/messages', name: 'send_message', methods: ['POST'])]
    #[OA\Post(
        path: '/api/messages',
        summary: 'Envoyer un message à un utilisateur',
        description: 'Permet à un utilisateur authentifié d’envoyer un message à un autre utilisateur.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['contenue', 'receiver_id'],
                properties: [
                    new OA\Property(property: 'contenue', type: 'string', example: 'Bonjour, comment ça va ?'),
                    new OA\Property(property: 'receiver_id', type: 'integer', example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Message envoyé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Message envoyé'),
                        new OA\Property(property: 'id', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Champs manquants',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Tous les champs sont requis')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Utilisateur non connecté',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Utilisateur non connecté')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Destinataire introuvable',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Destinataire introuvable')
                    ]
                )
            )
        ],
        tags: ['Messages']
    )]
    public function sendMessage(Request $request, Security $security): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['contenue']) || empty($data['receiver_id'])) {
            return $this->json(['message' => 'Tous les champs sont requis'], 400);
        }

        /** @var User $sender */
        $sender = $security->getUser();
        if (!$sender) {
            return $this->json(['message' => 'Utilisateur non connecté'], 401);
        }

        $receiver = $this->userRepo->find($data['receiver_id']);
        if (!$receiver) {
            return $this->json(['message' => 'Destinataire introuvable'], 404);
        }

        $message = new Message();
        $message->setContenue($data['contenue']);
        $message->setDate(new \DateTime());
        $message->setSendeur($sender);
        $message->setReceive($receiver);

        $this->em->persist($message);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Message envoyé',
            'id' => $message->getId(),
        ]);
    }

    // 📋 Liste des conversations
    #[Route('/api/messages/conversations', methods: ['GET'])]
    #[OA\Get(
        path: '/api/messages/conversations',
        summary: 'Récupérer la liste des conversations',
        description: 'Renvoie la dernière conversation avec chaque utilisateur pour l’utilisateur authentifié.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des conversations',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'userId', type: 'integer', example: 2),
                            new OA\Property(property: 'userName', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'userPhoto', type: 'string', example: 'photo_url.jpg'),
                            new OA\Property(property: 'lastMessage', type: 'string', example: 'Salut !'),
                            new OA\Property(property: 'lastMessageDate', type: 'string', example: '2026-02-03 16:55:00'),
                            new OA\Property(property: 'unreadCount', type: 'integer', example: 0)
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Non authentifié')
                    ]
                )
            )
        ],
        tags: ['Messages']
    )]
    public function getConversations(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Non authentifié'], 401);
        }

        $messages = $this->messageRepo->createQueryBuilder('m')
            ->where('m.sendeur = :user OR m.receive = :user')
            ->setParameter('user', $user)
            ->orderBy('m.date', 'DESC')
            ->getQuery()
            ->getResult();

        $conversations = [];
        foreach ($messages as $message) {
            $otherUser = $message->getSendeur() === $user ? $message->getReceive() : $message->getSendeur();
            $otherUserId = $otherUser->getId();

            if (!isset($conversations[$otherUserId])) {
                $conversations[$otherUserId] = [
                    'userId' => $otherUserId,
                    'userName' => $otherUser->getNom() . ' ' . $otherUser->getPrenom(),
                    'userPhoto' => $otherUser->getPhoto(),
                    'lastMessage' => $message->getContenue(),
                    'lastMessageDate' => $message->getDate()->format('Y-m-d H:i:s'),
                    'unreadCount' => 0,
                ];
            }
        }

        return new JsonResponse(array_values($conversations));
    }

    // 💬 Messages d'une conversation
    #[Route('/api/messages/conversation/{otherUserId}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/messages/conversation/{otherUserId}',
        summary: 'Récupérer les messages avec un utilisateur',
        parameters: [
            new OA\Parameter(name: 'otherUserId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Messages de la conversation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'messages', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'contenue', type: 'string', example: 'Salut !'),
                                new OA\Property(property: 'date', type: 'string', example: '2026-02-03 16:55:00'),
                                new OA\Property(property: 'senderId', type: 'integer', example: 1),
                                new OA\Property(property: 'receiverId', type: 'integer', example: 2),
                                new OA\Property(property: 'senderName', type: 'string', example: 'Rokhaya Touré'),
                                new OA\Property(property: 'senderPhoto', type: 'string', example: 'photo_url.jpg'),
                                new OA\Property(property: 'isMine', type: 'boolean', example: true)
                            ]
                        )),
                        new OA\Property(property: 'otherUser', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 2),
                            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'photo', type: 'string', example: 'photo_url.jpg')
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Utilisateur non authentifié'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable')
        ],
        tags: ['Messages']
    )]
    public function getConversation(int $otherUserId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['message' => 'Utilisateur non authentifié'], 401);

        $otherUser = $this->userRepo->find($otherUserId);
        if (!$otherUser) return new JsonResponse(['message' => 'Utilisateur introuvable'], 404);

        $messages = $this->messageRepo->createQueryBuilder('m')
            ->where('(m.sendeur = :user AND m.receive = :otherUser) OR (m.sendeur = :otherUser AND m.receive = :user)')
            ->setParameter('user', $user)
            ->setParameter('otherUser', $otherUser)
            ->orderBy('m.date', 'ASC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($messages as $message) {
            $data[] = [
                'id' => $message->getId(),
                'contenue' => $message->getContenue(),
                'date' => $message->getDate()->format('Y-m-d H:i:s'),
                'senderId' => $message->getSendeur()->getId(),
                'receiverId' => $message->getReceive()->getId(),
                'senderName' => $message->getSendeur()->getNom() . ' ' . $message->getSendeur()->getPrenom(),
                'senderPhoto' => $message->getSendeur()->getPhoto(),
                'isMine' => $message->getSendeur()->getId() === $user->getId(),
            ];
        }

        return new JsonResponse([
            'messages' => $data,
            'otherUser' => [
                'id' => $otherUser->getId(),
                'name' => $otherUser->getNom() . ' ' . $otherUser->getPrenom(),
                'photo' => $otherUser->getPhoto(),
            ]
        ]);
    }
}
