<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use App\Enum\ReservationStatus;
use App\Enum\TrajetStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ReservationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationController $notificationController,
    ) {}

    // ============================================
    // 1️⃣ Créer une réservation (passager)
    // ============================================
    #[Route('/reservation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        /** @var User $passager */
        $passager = $this->getUser();
        if (!$passager instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Récupération du trajet
        $trajet = $this->em->getRepository(Trajet::class)->find($data['trajet'] ?? null);
        if (!$trajet) {
            return $this->json(['error' => 'Trajet introuvable'], 404);
        }

        // Empêcher le conducteur de réserver son propre trajet
        if ($passager->getId() === $trajet->getConducteur()->getId()) {
            return $this->json(['error' => 'Vous ne pouvez pas réserver votre propre trajet'], 400);
        }

        // Validation du nombre de places
        $placesDemandes = (int) ($data['place'] ?? 1);
        if ($placesDemandes < 1) {
            return $this->json(['error' => 'Nombre de places invalide'], 400);
        }

        // Vérifier les doublons
        $existingReservation = $this->em->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->where('r.utilisateur = :user')
            ->andWhere('r.trajet = :trajet')
            ->andWhere('r.statut IN (:statuts)')
            ->setParameter('user', $passager)
            ->setParameter('trajet', $trajet)
            ->setParameter('statuts', ['en_attente', 'acceptee', 'payee'])
            ->getQuery()
            ->getOneOrNullResult();

        if ($existingReservation) {
            return $this->json(['error' => 'Vous avez déjà une réservation active pour ce trajet'], 400);
        }

        // Calcul des places occupées
        $placesOccupees = (int) $this->em->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.place), 0)')
            ->where('r.trajet = :trajet')
            ->andWhere('r.statut IN (:statuts)')
            ->setParameter('trajet', $trajet)
            ->setParameter('statuts', ['en_attente', 'acceptee', 'payee'])
            ->getQuery()
            ->getSingleScalarResult();

        if ($placesOccupees + $placesDemandes > $trajet->getPlacesDisponibles()) {
            return $this->json([
                'error' => 'Plus assez de places disponibles',
                'places_restantes' => $trajet->getPlacesDisponibles() - $placesOccupees
            ], 400);
        }

        // Création de la réservation
        $reservation = new Reservation();
        $reservation
            ->setUtilisateur($passager)
            ->setTrajet($trajet)
            ->setPlace($placesDemandes)
            ->setStatut(ReservationStatus::EN_ATTENTE);

        $this->em->persist($reservation);
        $this->em->flush();

        // ✅ NOTIFICATION AU CONDUCTEUR
        $this->notificationController->notify(
            $trajet->getConducteur(),
            "Nouvelle demande de réservation de {$passager->getPrenom()} {$passager->getNom()} pour votre trajet {$trajet->getDepart()} → {$trajet->getArrivee()} ({$placesDemandes} place(s))",
            $reservation->getId(),
            'reservation_demande'
        );

        return $this->json([
            'message' => 'Demande de réservation envoyée avec succès',
            'id' => $reservation->getId(),
            'statut' => $reservation->getStatut()->value,
        ], 201);
    }

    // ============================================
    // 2️⃣ Confirmer une réservation (conducteur)
    // ============================================
    #[Route('/reservation/{id}/confirmer', methods: ['PATCH'])]
    public function confirmer(int $id): JsonResponse
    {
        $reservation = $this->em->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            return $this->json(['error' => 'Réservation introuvable'], 404);
        }

        /** @var User $conducteur */
        $conducteur = $this->getUser();
        if (!$conducteur instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        if ($reservation->getTrajet()->getConducteur()->getId() !== $conducteur->getId()) {
            return $this->json(['error' => 'Accès interdit'], 403);
        }

        if ($reservation->getStatut() !== ReservationStatus::EN_ATTENTE) {
            return $this->json(['error' => 'Cette réservation ne peut pas être confirmée'], 400);
        }

        $reservation->setStatut(ReservationStatus::ACCEPTEE);
        $this->em->flush();

        // ✅ NOTIFICATION AU PASSAGER
        $this->notificationController->notify(
            $reservation->getUtilisateur(),
            "Votre réservation pour le trajet {$reservation->getTrajet()->getDepart()} → {$reservation->getTrajet()->getArrivee()} a été confirmée par {$conducteur->getPrenom()}",
            $reservation->getId(),
            'reservation_acceptee'
        );

        return $this->json([
            'message' => 'Réservation confirmée',
            'statut' => $reservation->getStatut()->value
        ]);
    }

    // ============================================
    // 3️⃣ Annuler une réservation (conducteur ou passager)
    // ============================================
    #[Route('/reservation/{id}/annuler', methods: ['PATCH'])]
    public function annuler(int $id): JsonResponse
    {
        $reservation = $this->em->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            return $this->json(['error' => 'Réservation introuvable'], 404);
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $isPassager = $reservation->getUtilisateur()->getId() === $user->getId();
        $isConducteur = $reservation->getTrajet()->getConducteur()->getId() === $user->getId();

        if (!$isPassager && !$isConducteur) {
            return $this->json(['error' => 'Accès interdit'], 403);
        }

        $reservation->setStatut(ReservationStatus::ANNULEE);
        $this->em->flush();

        // ✅ NOTIFICATION
        if ($isPassager) {
            // Passager annule → notifier conducteur
            $this->notificationController->notify(
                $reservation->getTrajet()->getConducteur(),
                "{$user->getPrenom()} a annulé sa réservation pour votre trajet {$reservation->getTrajet()->getDepart()} → {$reservation->getTrajet()->getArrivee()}",
                $reservation->getId(),
                'reservation_annulee'
            );
        } else {
            // Conducteur refuse → notifier passager
            $this->notificationController->notify(
                $reservation->getUtilisateur(),
                "Votre demande de réservation pour le trajet {$reservation->getTrajet()->getDepart()} → {$reservation->getTrajet()->getArrivee()} a été refusée",
                $reservation->getId(),
                'reservation_refusee'
            );
        }

        return $this->json(['message' => 'Réservation annulée']);
    }

    // ============================================
    // 4️⃣ Récupérer mes réservations (passager)
    // ============================================
    #[Route('/utilisateur/reservations', methods: ['GET'])]
    public function getMesReservations(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $reservations = $this->em->getRepository(Reservation::class)
            ->findBy(['utilisateur' => $user], ['id' => 'DESC']);

        $data = array_map(function(Reservation $reservation) {
            return [
                'id' => $reservation->getId(),
                'trajet_id' => $reservation->getTrajet()->getId(),
                'depart' => $reservation->getTrajet()->getDepart(),
                'arrivee' => $reservation->getTrajet()->getArrivee(),
                'date' => $reservation->getTrajet()->getDate()->format('Y-m-d'),
                'heure' => $reservation->getTrajet()->getHeure()->format('H:i:s'),
                'prix' => $reservation->getTrajet()->getPrix(),
                'place' => $reservation->getPlace(),
                'statut' => $reservation->getStatut()->value,
                'conducteur_nom' => $reservation->getTrajet()->getConducteur()->getNom(),
                'conducteur_prenom' => $reservation->getTrajet()->getConducteur()->getPrenom(),
            ];
        }, $reservations);

        return $this->json([
            'reservations' => $data,
            'total' => count($data)
        ]);
    }

    // ============================================
    // 5️⃣ Récupérer les réservations du conducteur
    // ============================================
    #[Route('/conducteur/reservations', methods: ['GET'])]
    public function getConducteurReservations(): JsonResponse
    {
        /** @var User $conducteur */
        $conducteur = $this->getUser();
        if (!$conducteur instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Récupérer tous les trajets du conducteur
        $trajets = $this->em->getRepository(Trajet::class)
            ->findBy(['conducteur' => $conducteur]);

        // Récupérer toutes les réservations de ces trajets
        $reservations = [];
        foreach ($trajets as $trajet) {
            $trajetReservations = $this->em->getRepository(Reservation::class)
                ->findBy(['trajet' => $trajet], ['id' => 'DESC']);
            $reservations = array_merge($reservations, $trajetReservations);
        }

        $data = array_map(function(Reservation $reservation) {
            return [
                'id' => $reservation->getId(),
                'trajet_id' => $reservation->getTrajet()->getId(),
                'depart' => $reservation->getTrajet()->getDepart(),
                'arrivee' => $reservation->getTrajet()->getArrivee(),
                'date' => $reservation->getTrajet()->getDate()->format('Y-m-d'),
                'heure' => $reservation->getTrajet()->getHeure()->format('H:i:s'),
                'prix' => $reservation->getTrajet()->getPrix(),
                'place' => $reservation->getPlace(),
                'statut' => $reservation->getStatut()->value,
                'passager_nom' => $reservation->getUtilisateur()->getNom(),
                'passager_prenom' => $reservation->getUtilisateur()->getPrenom(),
            ];
        }, $reservations);

        return $this->json([
            'reservations' => $data,
            'total' => count($data)
        ]);
    }

    // ============================================
    // 6️⃣ Récupérer les réservations d'un trajet spécifique
    // ============================================
    #[Route('/trajet/{id}/reservations', methods: ['GET'])]
    public function getTrajetReservations(int $id): JsonResponse
    {
        $trajet = $this->em->getRepository(Trajet::class)->find($id);
        if (!$trajet) {
            return $this->json(['error' => 'Trajet introuvable'], 404);
        }

        /** @var User $conducteur */
        $conducteur = $this->getUser();
        if (!$conducteur instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        if ($trajet->getConducteur()->getId() !== $conducteur->getId()) {
            return $this->json(['error' => 'Accès interdit'], 403);
        }

        $reservations = $this->em->getRepository(Reservation::class)
            ->findBy(['trajet' => $trajet], ['id' => 'DESC']);

        $data = array_map(function(Reservation $reservation) {
            return [
                'id' => $reservation->getId(),
                'place' => $reservation->getPlace(),
                'statut' => $reservation->getStatut()->value,
                'passager_nom' => $reservation->getUtilisateur()->getNom(),
                'passager_prenom' => $reservation->getUtilisateur()->getPrenom(),
            ];
        }, $reservations);

        return $this->json([
            'reservations' => $data,
            'total_reservations' => count($data)
        ]);
    }
}