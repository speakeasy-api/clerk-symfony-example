<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api', name: 'api_')]
class ProtectedController extends AbstractController
{
    #[Route('/clerk-jwt', name: 'clerk_jwt', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function clerkJwt(): JsonResponse
    {
        return $this->json([
            'userId' => $this->getUser()->getUserIdentifier()
        ]);
    }

    #[Route('/get-gated', name: 'get_gated', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getGated(): JsonResponse
    {
        return $this->json([
            'foo' => 'bar', 
        ]);
    }
} 