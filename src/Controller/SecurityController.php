<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class SecurityController extends AbstractController
{
     #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
public function logout(): JsonResponse
{
    return new JsonResponse([
        'message' => 'Déconnexion réussie'
    ], 200);
}

}



