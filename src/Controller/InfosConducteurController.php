<?php

namespace App\Controller;

use App\Entity\InfosConducteur;
use App\Entity\User;
use App\Enum\StatutValidation;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Conducteurs')]
final class InfosConducteurController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

  

#[Route('/api/infos-conducteur', name: 'register_infos_conducteur', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(
        path: '/api/infos-conducteur',
        summary: 'Soumettre une demande pour devenir conducteur',
        description: 'Permet à un utilisateur de soumettre sa demande pour devenir conducteur. La demande sera en attente de validation par un administrateur.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['numeropermis', 'dateemission', 'payededelivrance'],
                properties: [
                    new OA\Property(property: 'numeropermis', type: 'string', example: 'SN123456789', description: 'Numéro du permis de conduire'),
                    new OA\Property(property: 'dateemission', type: 'string', format: 'date', example: '2023-06-15', description: 'Date d’émission du permis'),
                    new OA\Property(property: 'payededelivrance', type: 'string', example: 'Sénégal', description: 'Pays où le permis a été délivré')
                ],
                example: [
                    'numeropermis' => 'SN123456789',
                    'dateemission' => '2023-06-15',
                    'payededelivrance' => 'Sénégal'
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Demande soumise avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Demande soumise avec succès. En attente de validation par un administrateur.'),
                        new OA\Property(property: 'infos_conducteur', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'numeropermis', type: 'string', example: 'SN123456789'),
                            new OA\Property(property: 'dateemission', type: 'string', example: '2023-06-15'),
                            new OA\Property(property: 'payededelivrance', type: 'string', example: 'Sénégal'),
                            new OA\Property(property: 'statut', type: 'string', example: 'EN_ATTENTE'),
                            new OA\Property(property: 'statut_label', type: 'string', example: 'En attente'),
                            new OA\Property(property: 'created_at', type: 'string', example: '2026-02-03 16:50:00')
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Champs manquants ou invalides',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Tous les champs sont requis')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Utilisateur non authentifié',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Utilisateur non authentifié')
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: 'Demande déjà existante ou utilisateur déjà conducteur',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Vous êtes déjà conducteur validé')
                    ]
                )
            )
        ],
        
    )]
    public function registerInfosConducteur(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $numeropermis = $data['numeropermis'] ?? null;
        $dateemission = $data['dateemission'] ?? null;
        $payededelivrance = $data['payededelivrance'] ?? null;

        if (!$numeropermis || !$dateemission || !$payededelivrance) {
            return new JsonResponse(['message' => 'Tous les champs sont requis'], 400);
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || !$user instanceof User) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], 401);
        }

        $existingInfos = $this->em->getRepository(InfosConducteur::class)
            ->findOneBy(['utilisateur' => $user]);

        if ($existingInfos) {
            if ($existingInfos->isEnAttente()) {
                return new JsonResponse(['message' => 'Vous avez déjà une demande en attente de validation'], 400);
            }
            if ($existingInfos->isValide()) {
                return new JsonResponse(['message' => 'Vous êtes déjà conducteur validé'], 400);
            }
            if ($existingInfos->isRejete()) {
                $this->em->remove($existingInfos);
                $this->em->flush();
            }
        }

        $date = \DateTime::createFromFormat('Y-m-d', $dateemission);
        if (!$date) {
            return new JsonResponse(['message' => 'Format de date invalide (Y-m-d)'], 400);
        }

        $infosConducteur = new InfosConducteur();
        $infosConducteur
            ->setUtilisateur($user)
            ->setNumeropermis($numeropermis)
            ->setDateemission($date)
            ->setPayededelivrance($payededelivrance)
            ->setStatut(StatutValidation::EN_ATTENTE);

        $this->em->persist($infosConducteur);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Demande soumise avec succès. En attente de validation par un administrateur.',
            'infos_conducteur' => [
                'id' => $infosConducteur->getId(),
                'numeropermis' => $infosConducteur->getNumeropermis(),
                'dateemission' => $infosConducteur->getDateemission()->format('Y-m-d'),
                'payededelivrance' => $infosConducteur->getPayededelivrance(),
                'statut' => $infosConducteur->getStatut()->value,
                'statut_label' => $infosConducteur->getStatut()->getLabel(),
                'created_at' => $infosConducteur->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ], 201);
    }


#[Route('/api/admin/demandes-conducteur', name: 'admin_list_demandes', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
#[OA\Get(
    path: '/api/admin/demandes-conducteur',
    summary: 'Lister toutes les demandes conducteur',
    description: 'Permet à un administrateur de consulter toutes les demandes pour devenir conducteur avec filtrage par statut',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'statut',
            in: 'query',
            required: false,
            description: 'Filtrer par statut (en_attente, valide, rejete)',
            schema: new OA\Schema(type: 'string', enum: ['en_attente', 'valide', 'rejete'])
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Liste des demandes',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'total', type: 'integer', example: 15),
                    new OA\Property(
                        property: 'demandes',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'numeropermis', type: 'string', example: 'SN123456789'),
                                new OA\Property(property: 'dateemission', type: 'string', example: '2023-06-15'),
                                new OA\Property(property: 'payededelivrance', type: 'string', example: 'Sénégal'),
                                new OA\Property(property: 'statut', type: 'string', example: 'EN_ATTENTE'),
                                new OA\Property(property: 'statut_label', type: 'string', example: 'En attente'),
                                new OA\Property(property: 'created_at', type: 'string', example: '2026-02-03 16:50:00'),
                                new OA\Property(
                                    property: 'utilisateur',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 5),
                                        new OA\Property(property: 'nom', type: 'string', example: 'Diop'),
                                        new OA\Property(property: 'prenom', type: 'string', example: 'Moussa'),
                                        new OA\Property(property: 'email', type: 'string', example: 'moussa@example.com'),
                                        new OA\Property(property: 'telephone', type: 'string', example: '+221771234567')
                                    ]
                                )
                            ]
                        )
                    )
                ]
            )
        )
    ],
)]
public function listDemandesConducteur(Request $request): JsonResponse
{
    $statutFilter = $request->query->get('statut');
    
    $criteria = [];
    if ($statutFilter) {
        try {
            $criteria['statut'] = StatutValidation::from($statutFilter);
        } catch (\ValueError $e) {
            return new JsonResponse(['message' => 'Statut invalide'], 400);
        }
    }

    $demandes = $this->em->getRepository(InfosConducteur::class)
        ->findBy($criteria, ['createdAt' => 'DESC']);

    $result = [];
    foreach ($demandes as $demande) {
        $user = $demande->getUtilisateur();
        $item = [
            'id' => $demande->getId(),
            'numeropermis' => $demande->getNumeropermis(),
            'dateemission' => $demande->getDateemission()->format('Y-m-d'),
            'payededelivrance' => $demande->getPayededelivrance(),
            'statut' => $demande->getStatut()->value,
            'statut_label' => $demande->getStatut()->getLabel(),
            'created_at' => $demande->getCreatedAt()->format('Y-m-d H:i:s'),
            'utilisateur' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'telephone' => $user->getTelephone()
            ]
        ];

        if ($demande->getDateValidation()) {
            $item['date_validation'] = $demande->getDateValidation()->format('Y-m-d H:i:s');
        }

        if ($demande->getValidePar()) {
            $validateur = $demande->getValidePar();
            $item['valide_par'] = [
                'id' => $validateur->getId(),
                'nom' => $validateur->getNom(),
                'prenom' => $validateur->getPrenom()
            ];
        }

        if ($demande->getMotifRejet()) {
            $item['motif_rejet'] = $demande->getMotifRejet();
        }

        $result[] = $item;
    }

    return new JsonResponse([
        'total' => count($result),
        'demandes' => $result
    ], 200);
}

#[Route('/api/admin/demandes-conducteur/{id}', name: 'admin_detail_demande', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
#[OA\Get(
    path: '/api/admin/demandes-conducteur/{id}',
    summary: 'Voir le détail d\'une demande conducteur',
    description: 'Permet à un administrateur de consulter tous les détails d\'une demande spécifique',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la demande',
            schema: new OA\Schema(type: 'integer')
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Détails de la demande',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'numeropermis', type: 'string', example: 'SN123456789'),
                    new OA\Property(property: 'dateemission', type: 'string', example: '2023-06-15'),
                    new OA\Property(property: 'payededelivrance', type: 'string', example: 'Sénégal'),
                    new OA\Property(property: 'statut', type: 'string', example: 'EN_ATTENTE'),
                    new OA\Property(property: 'statut_label', type: 'string', example: 'En attente'),
                    new OA\Property(property: 'created_at', type: 'string', example: '2026-02-03 16:50:00'),
                    new OA\Property(property: 'updated_at', type: 'string', example: '2026-02-03 16:50:00')
                ]
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Demande non trouvée'
        )
    ],

)]
public function detailDemandeConducteur(int $id): JsonResponse
{
    $demande = $this->em->getRepository(InfosConducteur::class)->find($id);

    if (!$demande) {
        return new JsonResponse(['message' => 'Demande non trouvée'], 404);
    }

    $user = $demande->getUtilisateur();
    
    $response = [
        'id' => $demande->getId(),
        'numeropermis' => $demande->getNumeropermis(),
        'dateemission' => $demande->getDateemission()->format('Y-m-d'),
        'payededelivrance' => $demande->getPayededelivrance(),
        'statut' => $demande->getStatut()->value,
        'statut_label' => $demande->getStatut()->getLabel(),
        'created_at' => $demande->getCreatedAt()->format('Y-m-d H:i:s'),
        'updated_at' => $demande->getUpdatedAt()->format('Y-m-d H:i:s'),
        'utilisateur' => [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'telephone' => $user->getTelephone()
        ]
    ];

    if ($demande->getDateValidation()) {
        $response['date_validation'] = $demande->getDateValidation()->format('Y-m-d H:i:s');
    }

    if ($demande->getValidePar()) {
        $validateur = $demande->getValidePar();
        $response['valide_par'] = [
            'id' => $validateur->getId(),
            'nom' => $validateur->getNom(),
            'prenom' => $validateur->getPrenom(),
            'email' => $validateur->getEmail()
        ];
    }

    if ($demande->getMotifRejet()) {
        $response['motif_rejet'] = $demande->getMotifRejet();
    }

    return new JsonResponse($response, 200);
}

#[Route('/api/admin/demandes-conducteur/{id}/valider', name: 'admin_valider_demande', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
#[OA\Post(
    path: '/api/admin/demandes-conducteur/{id}/valider',
    summary: 'Valider une demande conducteur',
    description: 'Permet à un administrateur de valider une demande et d\'attribuer le rôle CONDUCTEUR à l\'utilisateur',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la demande',
            schema: new OA\Schema(type: 'integer')
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Demande validée avec succès',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Demande validée avec succès. L\'utilisateur est maintenant conducteur.'),
                    new OA\Property(
                        property: 'demande',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'statut', type: 'string', example: 'VALIDE'),
                            new OA\Property(property: 'date_validation', type: 'string', example: '2026-02-05 10:30:00')
                        ]
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Demande déjà traitée'
        ),
        new OA\Response(
            response: 404,
            description: 'Demande non trouvée'
        )
    ],
    
)]
public function validerDemandeConducteur(int $id): JsonResponse
{
    /** @var User $admin */
    $admin = $this->getUser();
    
    $demande = $this->em->getRepository(InfosConducteur::class)->find($id);

    if (!$demande) {
        return new JsonResponse(['message' => 'Demande non trouvée'], 404);
    }

    if (!$demande->isEnAttente()) {
        return new JsonResponse([
            'message' => 'Cette demande a déjà été traitée',
            'statut_actuel' => $demande->getStatut()->getLabel()
        ], 400);
    }

    // Mise à jour du statut de la demande
    $demande->setStatut(StatutValidation::VALIDE);
    $demande->setDateValidation(new \DateTime());
    $demande->setValidePar($admin);
    $demande->setMotifRejet(null);

    // Attribuer le rôle CONDUCTEUR à l'utilisateur
    $user = $demande->getUtilisateur();
    $roles = $user->getRoles();
    if (!in_array('ROLE_CONDUCTEUR', $roles, true)) {
        $roles[] = 'ROLE_CONDUCTEUR';
        $user->setRoles($roles);
    }

    $this->em->flush();

    return new JsonResponse([
        'message' => 'Demande validée avec succès. L\'utilisateur est maintenant conducteur.',
        'demande' => [
            'id' => $demande->getId(),
            'statut' => $demande->getStatut()->value,
            'statut_label' => $demande->getStatut()->getLabel(),
            'date_validation' => $demande->getDateValidation()->format('Y-m-d H:i:s'),
            'utilisateur' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'roles' => $user->getRoles()
            ]
        ]
    ], 200);
}

#[Route('/api/admin/demandes-conducteur/{id}/rejeter', name: 'admin_rejeter_demande', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
#[OA\Post(
    path: '/api/admin/demandes-conducteur/{id}/rejeter',
    summary: 'Rejeter une demande conducteur',
    description: 'Permet à un administrateur de rejeter une demande avec un motif',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'ID de la demande',
            schema: new OA\Schema(type: 'integer')
        )
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['motif'],
            properties: [
                new OA\Property(
                    property: 'motif',
                    type: 'string',
                    example: 'Permis de conduire expiré',
                    description: 'Motif du rejet de la demande'
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Demande rejetée avec succès',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Demande rejetée'),
                    new OA\Property(
                        property: 'demande',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'statut', type: 'string', example: 'REJETE'),
                            new OA\Property(property: 'motif_rejet', type: 'string', example: 'Permis de conduire expiré'),
                            new OA\Property(property: 'date_validation', type: 'string', example: '2026-02-05 10:30:00')
                        ]
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Motif manquant ou demande déjà traitée'
        ),
        new OA\Response(
            response: 404,
            description: 'Demande non trouvée'
        )
    ],
)]
public function rejeterDemandeConducteur(int $id, Request $request): JsonResponse
{
    /** @var User $admin */
    $admin = $this->getUser();
    
    $data = json_decode($request->getContent(), true);
    $motif = $data['motif'] ?? null;

    if (!$motif || trim($motif) === '') {
        return new JsonResponse(['message' => 'Le motif de rejet est requis'], 400);
    }

    $demande = $this->em->getRepository(InfosConducteur::class)->find($id);

    if (!$demande) {
        return new JsonResponse(['message' => 'Demande non trouvée'], 404);
    }

    if (!$demande->isEnAttente()) {
        return new JsonResponse([
            'message' => 'Cette demande a déjà été traitée',
            'statut_actuel' => $demande->getStatut()->getLabel()
        ], 400);
    }

    // Mise à jour du statut de la demande
    $demande->setStatut(StatutValidation::REJETE);
    $demande->setDateValidation(new \DateTime());
    $demande->setValidePar($admin);
    $demande->setMotifRejet($motif);

    // S'assurer que l'utilisateur n'a PAS le rôle CONDUCTEUR
    $user = $demande->getUtilisateur();
    $roles = $user->getRoles();
    if (in_array('ROLE_CONDUCTEUR', $roles, true)) {
        $roles = array_values(array_diff($roles, ['ROLE_CONDUCTEUR']));
        $user->setRoles($roles);
    }

    $this->em->flush();

    return new JsonResponse([
        'message' => 'Demande rejetée',
        'demande' => [
            'id' => $demande->getId(),
            'statut' => $demande->getStatut()->value,
            'statut_label' => $demande->getStatut()->getLabel(),
            'motif_rejet' => $demande->getMotifRejet(),
            'date_validation' => $demande->getDateValidation()->format('Y-m-d H:i:s'),
            'utilisateur' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom()
            ]
        ]
    ], 200);
}

















    #[Route('/api/infos-conducteur/statut', name: 'get_statut_demande', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Get(
        path: '/api/infos-conducteur/statut',
        summary: 'Vérifier le statut de sa demande conducteur',
        description: 'Permet de consulter l\'état de sa demande pour devenir conducteur',
        security: [['bearerAuth' => []]],
        tags: ['Conducteurs']
    )]
    public function getStatutDemande(): JsonResponse
    {
      
        $user = $this->getUser();

        $infosConducteur = $this->em->getRepository(InfosConducteur::class)
            ->findOneBy(['utilisateur' => $user]);

        if (!$infosConducteur) {
            return new JsonResponse([
                'message' => 'Aucune demande trouvée',
                'has_demande' => false
            ], 200);
        }

        $response = [
            'has_demande' => true,
            'id' => $infosConducteur->getId(),
            'statut' => $infosConducteur->getStatut()->value,
            'statut_label' => $infosConducteur->getStatut()->getLabel(),
            'numeropermis' => $infosConducteur->getNumeropermis(),
            'dateemission' => $infosConducteur->getDateemission()->format('Y-m-d'),
            'payededelivrance' => $infosConducteur->getPayededelivrance(),
            'created_at' => $infosConducteur->getCreatedAt()->format('Y-m-d H:i:s')
        ];

        if ($infosConducteur->getDateValidation()) {
            $response['date_validation'] = $infosConducteur->getDateValidation()->format('Y-m-d H:i:s');
        }

        if ($infosConducteur->getMotifRejet()) {
            $response['motif_rejet'] = $infosConducteur->getMotifRejet();
        }

        return new JsonResponse($response, 200);
    }

    #[Route('/api/infos-conducteur', name: 'get_infos_conducteur', methods: ['GET'])]
    #[IsGranted('ROLE_CONDUCTEUR')]
    #[OA\Get(
        path: '/api/infos-conducteur',
        summary: 'Obtenir les informations du permis de conduire',
        description: 'Récupère les informations du permis de conduire de l\'utilisateur connecté (conducteur validé)',
        security: [['bearerAuth' => []]],
        tags: ['Conducteurs']
    )]
    public function getInfosConducteur(): JsonResponse
    {
        
        $user = $this->getUser();

        $infosConducteur = $this->em->getRepository(InfosConducteur::class)
            ->findOneBy(['utilisateur' => $user]);

        if (!$infosConducteur) {
            return new JsonResponse(['message' => 'Aucune information conducteur trouvée'], 404);
        }

        return new JsonResponse([
            'id' => $infosConducteur->getId(),
            'numeropermis' => $infosConducteur->getNumeropermis(),
            'dateemission' => $infosConducteur->getDateemission()->format('Y-m-d'),
            'payededelivrance' => $infosConducteur->getPayededelivrance(),
            'statut' => $infosConducteur->getStatut()->value,
            'utilisateur' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail()
            ]
        ], 200);
    }

    #[Route('/api/infos-conducteur', name: 'update_infos_conducteur', methods: ['PUT'])]
    #[IsGranted('ROLE_CONDUCTEUR')]
    #[OA\Put(
        path: '/api/infos-conducteur',
        summary: 'Modifier les informations du permis de conduire',
        description: 'Met à jour les informations du permis (uniquement pour les conducteurs validés)',
        security: [['bearerAuth' => []]],
        tags: ['Conducteurs']
    )]
    public function updateInfosConducteur(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $infosConducteur = $this->em->getRepository(InfosConducteur::class)
            ->findOneBy(['utilisateur' => $user]);

        if (!$infosConducteur) {
            return new JsonResponse(['message' => 'Aucune information conducteur trouvée'], 404);
        }

        if (!$infosConducteur->isValide()) {
            return new JsonResponse([
                'message' => 'Vous devez être un conducteur validé pour modifier vos informations'
            ], 403);
        }

        if (isset($data['numeropermis'])) {
            $infosConducteur->setNumeropermis($data['numeropermis']);
        }

        if (isset($data['dateemission'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['dateemission']);
            if (!$date) {
                return new JsonResponse(['message' => 'Format de date invalide (Y-m-d)'], 400);
            }
            $infosConducteur->setDateemission($date);
        }

        if (isset($data['payededelivrance'])) {
            $infosConducteur->setPayededelivrance($data['payededelivrance']);
        }

        $this->em->flush();

        return new JsonResponse([
            'message' => 'Informations conducteur mises à jour',
            'infos_conducteur' => [
                'numeropermis' => $infosConducteur->getNumeropermis(),
                'dateemission' => $infosConducteur->getDateemission()->format('Y-m-d'),
                'payededelivrance' => $infosConducteur->getPayededelivrance()
            ]
        ], 200);
    }
}

