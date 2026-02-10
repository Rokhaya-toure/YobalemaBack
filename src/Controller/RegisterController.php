<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Utilisateurs')]
final class RegisterController extends AbstractController
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;
    
    public function __construct(
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $passwordHasher, 
        ValidatorInterface $validator
    ) {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Créer un nouvel utilisateur',
        description: 'Inscription d\'un utilisateur avec email, mot de passe, nom, prénom et téléphone. Le rôle ROLE_CONDUCTEUR sera attribué uniquement après validation de la demande par un administrateur.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'nom', 'prenom', 'telephone'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'rokhaya@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123', description: 'Minimum 6 caractères'),
                    new OA\Property(property: 'nom', type: 'string', example: 'Touré'),
                    new OA\Property(property: 'prenom', type: 'string', example: 'Rokhaya'),
                    new OA\Property(property: 'telephone', type: 'string', example: '771234567')
                ],
                example: [
                    'email' => 'rokhaya@example.com',
                    'password' => 'secret123',
                    'nom' => 'Touré',
                    'prenom' => 'Rokhaya',
                    'telephone' => '771234567'
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201, 
                description: 'Utilisateur créé avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Utilisateur créé avec succès'),
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'email', type: 'string', example: 'rokhaya@example.com'),
                            new OA\Property(property: 'nom', type: 'string', example: 'Touré'),
                            new OA\Property(property: 'prenom', type: 'string', example: 'Rokhaya'),
                            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER'])
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 400, 
                description: 'Données invalides',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Tous les champs sont requis')
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Email déjà utilisé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cet email est déjà utilisé')
                    ]
                )
            )
        ],
        tags: ['Utilisateurs']
    )]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $nom = $data['nom'] ?? null;
        $prenom = $data['prenom'] ?? null;
        $telephone = $data['telephone'] ?? null;

        if (!$email || !$password || !$nom || !$prenom || !$telephone) {
            return new JsonResponse(['message' => 'Tous les champs sont requis'], 400);
        }

        // Vérifier si l'email existe déjà
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return new JsonResponse(['message' => 'Cet email est déjà utilisé'], 409);
        }

        // Validation du mot de passe
        if (strlen($password) < 6) {
            return new JsonResponse(['message' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($email)
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setTelephone($telephone)
            ->setDateinscription(new \DateTime())
            ->setPassword($this->passwordHasher->hashPassword($user, $password));

        // ✅ Ajouter uniquement ROLE_USER par défaut
        // Le rôle ROLE_CONDUCTEUR sera ajouté après validation par l'admin
        $user->addRole(UserRole::ROLE_USER);

        // Validation Symfony
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'message' => 'Données invalides',
                'errors' => $errorMessages
            ], 400);
        }

        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'telephone' => $user->getTelephone(),
                'roles' => $user->getRoles(),
                'date_inscription' => $user->getDateinscription()->format('Y-m-d H:i:s')
            ],
            'next_step' => 'Pour devenir conducteur, veuillez soumettre vos informations de permis via /api/infos-conducteur'
        ], 201);
    }
}