<?php

namespace App\Controller;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProtectedController extends AbstractController
{
    #[Route('/clerk_jwt', name: 'userData', format: 'json')]
    public function clerkJwt(Request $request): JsonResponse
    {
        $authPayload = $request->attributes->get('verified_clerk_payload');
        if ($authPayload !== null) {
            return $this->json(['userId' => $request->attributes->get('verified_clerk_payload')->sub]);
        } else {
            return $this->json(['userId' => null]);
        }
    }

    #[Route('/get_gated', name: 'gated', format: 'json')]
    public function getGated(Request $request): JsonResponse
    {
        $authPayload = $request->attributes->get('verified_clerk_payload');
        if ($authPayload !== null && $authPayload->sub) {
            return $this->json(['foo' => 'bar']);
        }
        throw new AccessDeniedHttpException('You are not authorized to access this resource');
    }
}
