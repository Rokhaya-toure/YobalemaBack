<?php

namespace App\Controller;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
final class UserController extends AbstractController
{




     // 🔹 Lister uniquement les users simples : ROLE_USER mais pas ROLE_CONDUCTEUR ni ROLE_ADMIN
    #[Route('/role/simple-user', name: 'api_users_role_simple', methods: ['GET'])]
    public function listSimpleUsers(EntityManagerInterface $em): JsonResponse
    {
        $qb = $em->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u')
           ->where(':roleUser MEMBER OF u.roles')
           ->andWhere(':roleConducteur NOT MEMBER OF u.roles')
           ->andWhere(':roleAdmin NOT MEMBER OF u.roles')
           ->setParameter('roleUser', UserRole::ROLE_USER->value)
           ->setParameter('roleConducteur', UserRole::ROLE_CONDUCTEUR->value)
           ->setParameter('roleAdmin', UserRole::ROLE_ADMIN->value);

        $users = $qb->getQuery()->getResult();

        $data = array_map(fn(User $user) => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'roles' => $user->getRoles(),
            'date_inscription' => $user->getDateinscription()?->format('Y-m-d'),
            'photo' => $user->getPhoto(),
        ], $users);

        return new JsonResponse($data);
    }

    
// 🔹 Lister uniquement les conducteurs avec ROLE_CONDUCTEUR
    #[Route('/role/conducteur', name: 'api_users_role_conducteur', methods: ['GET'])]
    public function listConducteurs(EntityManagerInterface $em): JsonResponse
    {
        $allUsers = $em->getRepository(User::class)->findAll();

        $conducteurs = array_filter($allUsers, fn(User $user) => in_array(UserRole::ROLE_CONDUCTEUR->value, $user->getRoles()));

        $data = array_map(fn(User $user) => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'roles' => $user->getRoles(),
            'date_inscription' => $user->getDateinscription()?->format('Y-m-d'),
            'photo' => $user->getPhoto(),
        ], $conducteurs);

        return new JsonResponse($data);
    }




// 🔹 Rechercher un utilisateur par email, nom, prénom ou téléphone
    #[Route('/search', name: 'api_users_search', methods: ['GET'])]
    public function searchUser(EntityManagerInterface $em): JsonResponse
    {
        $keyword = $_GET['q'] ?? null;

        if (!$keyword) {
            return new JsonResponse(['message' => 'Mot-clé manquant'], 400);
        }

        $allUsers = $em->getRepository(User::class)->findAll();

        $filteredUsers = array_filter($allUsers, fn(User $user) =>
            str_contains(strtolower($user->getNom()), strtolower($keyword)) ||
            str_contains(strtolower($user->getPrenom()), strtolower($keyword)) ||
            str_contains(strtolower($user->getEmail()), strtolower($keyword)) ||
            str_contains(strtolower($user->getTelephone()), strtolower($keyword))
        );

        $data = array_map(fn(User $user) => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'roles' => $user->getRoles(),
            'date_inscription' => $user->getDateinscription()?->format('Y-m-d'),
            'photo' => $user->getPhoto(),
        ], $filteredUsers);

        return new JsonResponse($data);
    }
  /**
     * Récupérer les informations de l'utilisateur actuellement connecté.
     *
     * Cette route renvoie les informations de l'utilisateur authentifié.
     *
     * @Route("/me", name="api_me", methods={"GET"})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     *
     * @OA\Get(
     *     path="/me",
     *     summary="Récupère le profil de l'utilisateur connecté",
     *     description="Renvoie les informations de l'utilisateur actuellement authentifié",
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur récupéré avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="nom", type="string", example="Touré"),
     *             @OA\Property(property="prenom", type="string", example="Rokhaya"),
     *             @OA\Property(property="telephone", type="string", example="+221770000000"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"ROLE_USER"}),
     *             @OA\Property(property="date_inscription", type="string", format="date-time", example="2026-02-04 16:00:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Utilisateur non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Utilisateur non authentifié")
     *         )
     *     ),
     *     @Security(name="Bearer")
     * )
     */
   
    #[Route('/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getCurrentUser(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'roles' => $user->getRoles(),
            'date_inscription' => $user->getDateinscription()?->format('Y-m-d H:i:s'),
        ]);
    }



//  #[Route('/users', name: 'api_users_list', methods: ['GET'])]
//     #[IsGranted('ROLE_ADMIN')]
//     public function listUsers(EntityManagerInterface $em): JsonResponse
//     {
//         $users = $em->getRepository(User::class)->findAll();

//         $data = [];

//         foreach ($users as $user) {
//             $data[] = [
//                 'id' => $user->getId(),
//                 'email' => $user->getEmail(),
//                 'nom' => $user->getNom(),
//                 'prenom' => $user->getPrenom(),
//                 'telephone' => $user->getTelephone(),
//                 'roles' => $user->getRoles(),
//                 'date_inscription' => $user->getDateinscription()?->format('Y-m-d'),
//                 'photo' => $user->getPhoto(),
//             ];
//         }

//         return new JsonResponse($data);
//     }
    
 /**
     * Mettre à jour le profil de l'utilisateur connecté.
     *
     * Cette route permet de modifier le nom, prénom et téléphone de l'utilisateur authentifié.
     *
     * @Route("/me", name="api_update_me", methods={"PUT"})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     *
     * @OA\Put(
     *     path="/me",
     *     summary="Met à jour le profil de l'utilisateur connecté",
     *     description="Permet à l'utilisateur authentifié de mettre à jour ses informations personnelles",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="nom", type="string", example="Touré"),
     *             @OA\Property(property="prenom", type="string", example="Rokhaya"),
     *             @OA\Property(property="telephone", type="string", example="+221770000000")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Profil mis à jour avec succès"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="nom", type="string", example="Touré"),
     *                 @OA\Property(property="prenom", type="string", example="Rokhaya"),
     *                 @OA\Property(property="telephone", type="string", example="+221770000000"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"ROLE_USER"})
     *             )
     *         )
     *     ),
     *     @Security(name="Bearer")
     * )
     */
    #[Route('/me', name: 'api_update_me', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateProfile(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $user->setPrenom($data['prenom']);
        }
        if (isset($data['telephone'])) {
            $user->setTelephone($data['telephone']);
        }

        $em->flush();

        return new JsonResponse([
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'telephone' => $user->getTelephone(),
                'roles' => $user->getRoles(),
            ]
        ]);
    }












    /**
 * Lister tous les utilisateurs sauf les admins.
 *
 * @Route("/users", name="api_users_non_admins", methods={"GET"})
 * @IsGranted("ROLE_ADMIN")
 *
 * @OA\Get(
 *     path="/users/non-admins",
 *     summary="Liste tous les utilisateurs sauf les admins",
 *     description="Récupère la liste de tous les utilisateurs qui n'ont pas le rôle ROLE_ADMIN",
 *     @OA\Response(
 *         response=200,
 *         description="Liste des utilisateurs non-admins",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="email", type="string", example="user@example.com"),
 *                 @OA\Property(property="nom", type="string", example="Touré"),
 *                 @OA\Property(property="prenom", type="string", example="Rokhaya"),
 *                 @OA\Property(property="telephone", type="string", example="+221770000000"),
 *                 @OA\Property(property="roles", type="array", @OA\Items(type="string")),
 *                 @OA\Property(property="date_inscription", type="string", format="date"),
 *                 @OA\Property(property="photo", type="string", nullable=true)
 *             )
 *         )
 *     ),
 *     @Security(name="Bearer")
 * )
 */
#[Route('/users', name: 'api_users_non_admins', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
public function listNonAdminUsers(EntityManagerInterface $em): JsonResponse
{
    // Récupération de tous les utilisateurs
    $allUsers = $em->getRepository(User::class)->findAll();

    // Filtrage : exclure les utilisateurs ayant ROLE_ADMIN
    $nonAdminUsers = array_filter($allUsers, function(User $user) {
        return !in_array('ROLE_ADMIN', $user->getRoles());
    });

    // Formatage des données
    $data = [];
    foreach ($nonAdminUsers as $user) {
        $data[] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'roles' => $user->getRoles(),
            'date_inscription' => $user->getDateinscription()?->format('Y-m-d'),
            'photo' => $user->getPhoto(),
        ];
    }

    return new JsonResponse($data);
}

#[Route('/statistiques', name: 'api_statistiques', methods: ['GET'])]
public function statistiques(EntityManagerInterface $em): JsonResponse {
    try {
        $allUsers = $em->getRepository(User::class)->findAll();

        $admins = array_filter($allUsers, fn(User $user) => in_array(UserRole::ROLE_ADMIN->value, $user->getRoles()));
        $conducteurs = array_filter($allUsers, fn(User $user) => in_array(UserRole::ROLE_CONDUCTEUR->value, $user->getRoles()));
        $simpleUsers = array_filter($allUsers, fn(User $user) =>
            in_array(UserRole::ROLE_USER->value, $user->getRoles()) &&
            !in_array(UserRole::ROLE_CONDUCTEUR->value, $user->getRoles()) &&
            !in_array(UserRole::ROLE_ADMIN->value, $user->getRoles())
        );

        $total = count($allUsers);

        return $this->json([
            'total' => $total,
            'admins' => count($admins),
            'conducteurs' => count($conducteurs),
            'users' => count($simpleUsers)
        ]);
    } catch (\Exception $e) {
        return $this->json([
            'error' => $e->getMessage()
        ], 500);
    }
}

    // // 🔹 Liste les utilisateurs simples
    // #[Route('/role/simple-user', name: 'api_users_role_simple', methods: ['GET'])]
    // #[IsGranted('ROLE_ADMIN')]
    // public function listSimpleUsers(EntityManagerInterface $em): JsonResponse
    // {
    //     $qb = $em->createQueryBuilder();
    //     $qb->select('u')
    //        ->from(User::class, 'u')
    //        ->where(':roleUser MEMBER OF u.roles')
    //        ->andWhere(':roleConducteur NOT MEMBER OF u.roles')
    //        ->andWhere(':roleAdmin NOT MEMBER OF u.roles')
    //        ->setParameter('roleUser', UserRole::ROLE_USER->value)
    //        ->setParameter('roleConducteur', UserRole::ROLE_CONDUCTEUR->value)
    //        ->setParameter('roleAdmin', UserRole::ROLE_ADMIN->value);

    //     $users = $qb->getQuery()->getResult();

    //     $data = array_map(fn(User $user) => [
    //         'id' => $user->getId(),
    //         'email' => $user->getEmail(),
    //         'nom' => $user->getNom(),
    //         'prenom' => $user->getPrenom(),
    //         'telephone' => $user->getTelephone(),
    //         'roles' => $user->getRoles(),
    //         'date_inscription' => $user->getDateinscription()?->format('Y-m-d'),
    //         'photo' => $user->getPhoto(),
    //     ], $users);

    //     return new JsonResponse($data);
    // }



 
}


