<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class AvisController extends AbstractController
{
   private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    // CREATE
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $userId = $data['user_id'] ?? null;
        $vote = $data['vote'] ?? null;
        $commentaire = $data['commentaire'] ?? null;

        // if (!$userId || $vote === null || !$commentaire) {
        //     return new JsonResponse(['message' => 'Tous les champs sont requis'], 400);
        // }

        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        $avis = new Avis();
        $avis->setUtilisateur($user)
             ->setVote((int)$vote)
             ->setCommentaire($commentaire)
             ->setDate(new \DateTime());

        $this->em->persist($avis);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Avis créé avec succès',
            'data' => [
                'id' => $avis->getId(),
                'vote' => $avis->getVote(),
                'commentaire' => $avis->getCommentaire(),
                'date' => $avis->getDate()->format('Y-m-d H:i:s'),
                'utilisateur_id' => $user->getId()
            ]
        ], 201);
    }

    // READ ALL
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $avisList = $this->em->getRepository(Avis::class)->findAll();

        $data = array_map(fn(Avis $a) => [
            'id' => $a->getId(),
            'vote' => $a->getVote(),
            'commentaire' => $a->getCommentaire(),
            'date' => $a->getDate()->format('Y-m-d H:i:s'),
            'utilisateur_id' => $a->getUtilisateur()?->getId()
        ], $avisList);

        return new JsonResponse($data, 200);
    }

    // READ ONE
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $avis = $this->em->getRepository(Avis::class)->find($id);
        if (!$avis) {
            return new JsonResponse(['message' => 'Avis non trouvé'], 404);
        }

        return new JsonResponse([
            'id' => $avis->getId(),
            'vote' => $avis->getVote(),
            'commentaire' => $avis->getCommentaire(),
            'date' => $avis->getDate()->format('Y-m-d H:i:s'),
            'utilisateur_id' => $avis->getUtilisateur()?->getId()
        ], 200);
    }

    // UPDATE
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $avis = $this->em->getRepository(Avis::class)->find($id);
        if (!$avis) {
            return new JsonResponse(['message' => 'Avis non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $vote = $data['vote'] ?? null;
        $commentaire = $data['commentaire'] ?? null;

        if ($vote === null || !$commentaire) {
            return new JsonResponse(['message' => 'Tous les champs sont requis'], 400);
        }

        $avis->setVote((int)$vote)
             ->setCommentaire($commentaire)
             ->setDate(new \DateTime());

        $this->em->persist($avis);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Avis mis à jour avec succès',
            'data' => [
                'id' => $avis->getId(),
                'vote' => $avis->getVote(),
                'commentaire' => $avis->getCommentaire(),
                'date' => $avis->getDate()->format('Y-m-d H:i:s'),
                'utilisateur_id' => $avis->getUtilisateur()?->getId()
            ]
        ], 200);
    }

    // DELETE
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $avis = $this->em->getRepository(Avis::class)->find($id);
        if (!$avis) {
            return new JsonResponse(['message' => 'Avis non trouvé'], 404);
        }

        $this->em->remove($avis);
        $this->em->flush();

        return new JsonResponse(['message' => 'Avis supprimé avec succès'], 200);
    }
}
