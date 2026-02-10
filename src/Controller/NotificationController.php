<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class NotificationController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    // ============================================
    // 📤 Créer une notification (méthode interne)
    // ============================================
    public function notify(User $user, string $message, ?int $reservationId = null, string $type = 'general'): void
    {
        $notification = new Notification();
        $notification->setUser($user)
                     ->setContenue($message)
                     ->setReservationId($reservationId)
                     ->setType($type);

        $this->em->persist($notification);
        $this->em->flush();
    }

    // ============================================
    // 📋 Récupérer toutes les notifications
    // ============================================
    #[Route('/notifications', methods: ['GET'])]
    public function getNotifications(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $notifications = $this->em->getRepository(Notification::class)
            ->findBy(['user' => $user], ['id' => 'DESC']);

        $data = array_map(function(Notification $notification) {
            return [
                'id' => $notification->getId(),
                'message' => $notification->getContenue(),
                'lu' => $notification->isLue(),
                'created_at' => $notification->getCreatedAt()->format('c'),
                'reservation_id' => $notification->getReservationId(),
                'type' => $notification->getType(),
            ];
        }, $notifications);

        return $this->json($data);
    }

    // ============================================
    // 🔔 Récupérer les notifications non lues
    // ============================================
    #[Route('/notifications/non-lues', methods: ['GET'])]
    public function getNotificationsNonLues(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $notifications = $this->em->getRepository(Notification::class)
            ->findBy(['user' => $user, 'lue' => false], ['id' => 'DESC']);

        $data = array_map(function(Notification $notification) {
            return [
                'id' => $notification->getId(),
                'message' => $notification->getContenue(),
                'lu' => $notification->isLue(),
                'created_at' => $notification->getCreatedAt()->format('c'),
                'reservation_id' => $notification->getReservationId(),
                'type' => $notification->getType(),
            ];
        }, $notifications);

        return $this->json($data);
    }

    // ============================================
    // ✅ Marquer une notification comme lue
    // ============================================
    #[Route('/notifications/{id}/lire', methods: ['PATCH'])]
    public function marquerCommeLue(int $id): JsonResponse
    {
        $notification = $this->em->getRepository(Notification::class)->find($id);
        
        if (!$notification) {
            return $this->json(['error' => 'Notification introuvable'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($notification->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès interdit'], 403);
        }

        $notification->setLue(true);
        $this->em->flush();

        return $this->json(['message' => 'Notification marquée comme lue']);
    }

    // ============================================
    // ✅✅ Marquer toutes les notifications comme lues
    // ============================================
    #[Route('/notifications/tout-lire', methods: ['PATCH'])]
    public function marquerToutesCommeLues(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $this->em->createQueryBuilder()
            ->update(Notification::class, 'n')
            ->set('n.lue', true)
            ->where('n.user = :user')
            ->andWhere('n.lue = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        return $this->json(['message' => 'Toutes les notifications ont été marquées comme lues']);
    }

    // ============================================
    // 🗑️ Supprimer une notification
    // ============================================
    #[Route('/notifications/{id}', methods: ['DELETE'])]
    public function supprimerNotification(int $id): JsonResponse
    {
        $notification = $this->em->getRepository(Notification::class)->find($id);
        
        if (!$notification) {
            return $this->json(['error' => 'Notification introuvable'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($notification->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès interdit'], 403);
        }

        $this->em->remove($notification);
        $this->em->flush();

        return $this->json(['message' => 'Notification supprimée']);
    }
}