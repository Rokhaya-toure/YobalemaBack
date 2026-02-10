<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Repository\TrajetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TrajetController extends AbstractController
{



    
    private EntityManagerInterface $em;
    private TrajetRepository $trajetRepository;

    public function __construct(EntityManagerInterface $em, TrajetRepository $trajetRepository)
    {
        $this->em = $em;
        $this->trajetRepository = $trajetRepository;
    }

    // LIST ALL
    #[Route('/api/trajet_list', name: 'trajet_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $trajets = $this->trajetRepository->findAll();
        $data = [];

        foreach ($trajets as $trajet) {
            $data[] = [
                'id' => $trajet->getId(),
                'depart' => $trajet->getDepart(),
                'arrivee' => $trajet->getArrivee(),
                'date' => $trajet->getDate()->format('Y-m-d'),
                'heure' => $trajet->getHeure()->format('H:i:s'),
                'conducteur' => $trajet->getConducteur()->getId(),
                'conducteurNom' => $trajet->getConducteur()->getNom(),
                'conducteurPrenom' => $trajet->getConducteur()->getPrenom(),
                'departLat' => $trajet->getDepartLat(),
                'departLng' => $trajet->getDepartLng(),
                'arriveeLat' => $trajet->getArriveeLat(),
                'arriveeLng' => $trajet->getArriveeLng(),
                'prix' => $trajet->getPrix(),
                'placesDisponibles' => $trajet->getPlacesDisponibles(),
            ];
        }

        return $this->json($data);
    }

    // LIST BY USER (Trajets créés par un utilisateur)
    #[Route('/api/trajet_list/user/{userId}', name: 'trajet_list_by_user', methods: ['GET'])]
    public function listByUser(int $userId): JsonResponse
    {
        $trajets = $this->trajetRepository->findBy(['conducteur' => $userId]);
        $data = [];

        foreach ($trajets as $trajet) {
            $data[] = [
                'id' => $trajet->getId(),
                'depart' => $trajet->getDepart(),
                'arrivee' => $trajet->getArrivee(),
                'date' => $trajet->getDate()->format('Y-m-d'),
                'heure' => $trajet->getHeure()->format('H:i:s'),
                'conducteur' => $trajet->getConducteur()->getId(),
                'conducteurNom' => $trajet->getConducteur()->getNom(),
                'conducteurPrenom' => $trajet->getConducteur()->getPrenom(),
                'departLat' => $trajet->getDepartLat(),
                'departLng' => $trajet->getDepartLng(),
                'arriveeLat' => $trajet->getArriveeLat(),
                'arriveeLng' => $trajet->getArriveeLng(),
                'prix' => $trajet->getPrix(),
                'placesDisponibles' => $trajet->getPlacesDisponibles(),
            ];
        }

        return $this->json($data);
    }

    // SEARCH TRAJETS (Recherche par départ, arrivée, date)
    #[Route('/api/trajet_search', name: 'trajet_search', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $depart = $data['depart'] ?? null;
        $arrivee = $data['arrivee'] ?? null;
        $date = isset($data['date']) ? new \DateTime($data['date']) : null;

        $qb = $this->trajetRepository->createQueryBuilder('t');

        if ($depart) {
            $qb->andWhere('t.depart LIKE :depart')
               ->setParameter('depart', '%' . $depart . '%');
        }

        if ($arrivee) {
            $qb->andWhere('t.arrivee LIKE :arrivee')
               ->setParameter('arrivee', '%' . $arrivee . '%');
        }

        if ($date) {
            $qb->andWhere('t.date = :date')
               ->setParameter('date', $date);
        }

        // Trajets avec places disponibles uniquement
        $qb->andWhere('t.placesDisponibles > 0')
           ->orderBy('t.date', 'ASC')
           ->addOrderBy('t.heure', 'ASC');

        $trajets = $qb->getQuery()->getResult();
        $results = [];

        foreach ($trajets as $trajet) {
            $results[] = [
                'id' => $trajet->getId(),
                'depart' => $trajet->getDepart(),
                'arrivee' => $trajet->getArrivee(),
                'date' => $trajet->getDate()->format('Y-m-d'),
                'heure' => $trajet->getHeure()->format('H:i:s'),
                'conducteur' => $trajet->getConducteur()->getId(),
                'conducteurNom' => $trajet->getConducteur()->getNom(),
                'conducteurPrenom' => $trajet->getConducteur()->getPrenom(),
                'departLat' => $trajet->getDepartLat(),
                'departLng' => $trajet->getDepartLng(),
                'arriveeLat' => $trajet->getArriveeLat(),
                'arriveeLng' => $trajet->getArriveeLng(),
                'prix' => $trajet->getPrix(),
                'placesDisponibles' => $trajet->getPlacesDisponibles(),
            ];
        }

        return $this->json($results);
    }

    // CREATE
    #[Route('/api/trajet_create', name: 'trajet_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $trajet = new Trajet();
        $trajet->setDepart($data['depart']);
        $trajet->setArrivee($data['arrivee']);
        $trajet->setDate(new \DateTime($data['date']));
        $trajet->setHeure(new \DateTime($data['heure']));
        $trajet->setConducteur($this->em->getReference('App\Entity\User', $data['conducteur']));
        $trajet->setDepartLat((float)$data['departLat']);
        $trajet->setDepartLng((float)$data['departLng']);
        $trajet->setArriveeLat((float)$data['arriveeLat']);
        $trajet->setArriveeLng((float)$data['arriveeLng']);
        $trajet->setPrix((float)$data['prix']);
        $trajet->setPlacesDisponibles((int)$data['placesDisponibles']);

        $this->em->persist($trajet);
        $this->em->flush();

        return $this->json(['message' => 'Trajet créé', 'id' => $trajet->getId()], 201);
    }

    // READ
    #[Route('/api/trajet_list/{id}', name: 'trajet_show', methods: ['GET'])]
    public function show(Trajet $trajet): JsonResponse
    {
        return $this->json([
            'id' => $trajet->getId(),
            'depart' => $trajet->getDepart(),
            'arrivee' => $trajet->getArrivee(),
            'date' => $trajet->getDate()->format('Y-m-d'),
            'heure' => $trajet->getHeure()->format('H:i:s'),
            'conducteur' => $trajet->getConducteur()->getId(),
            'conducteurNom' => $trajet->getConducteur()->getNom(),
            'conducteurPrenom' => $trajet->getConducteur()->getPrenom(),
            'departLat' => $trajet->getDepartLat(),
            'departLng' => $trajet->getDepartLng(),
            'arriveeLat' => $trajet->getArriveeLat(),
            'arriveeLng' => $trajet->getArriveeLng(),
            'prix' => $trajet->getPrix(),
            'placesDisponibles' => $trajet->getPlacesDisponibles(),
        ]);
    }

    // UPDATE
    #[Route('/api/trajet_update/{id}', name: 'trajet_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Trajet $trajet): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $trajet->setDepart($data['depart'] ?? $trajet->getDepart());
        $trajet->setArrivee($data['arrivee'] ?? $trajet->getArrivee());
        if (isset($data['date'])) $trajet->setDate(new \DateTime($data['date']));
        if (isset($data['heure'])) $trajet->setHeure(new \DateTime($data['heure']));
        if (isset($data['conducteur'])) $trajet->setConducteur($this->em->getReference('App\Entity\User', $data['conducteur']));
        if (isset($data['departLat'])) $trajet->setDepartLat((float)$data['departLat']);
        if (isset($data['departLng'])) $trajet->setDepartLng((float)$data['departLng']);
        if (isset($data['arriveeLat'])) $trajet->setArriveeLat((float)$data['arriveeLat']);
        if (isset($data['arriveeLng'])) $trajet->setArriveeLng((float)$data['arriveeLng']);
        if (isset($data['prix'])) $trajet->setPrix((float)$data['prix']);
        if (isset($data['placesDisponibles'])) $trajet->setPlacesDisponibles((int)$data['placesDisponibles']);

        $this->em->flush();

        return $this->json(['message' => 'Trajet mis à jour']);
    }

    // DELETE
    #[Route('/api/trajet_delete/{id}', name: 'trajet_delete', methods: ['DELETE'])]
    public function delete(Trajet $trajet): JsonResponse
    {
        $this->em->remove($trajet);
        $this->em->flush();

        return $this->json(['message' => 'Trajet supprimé']);
    }
}
