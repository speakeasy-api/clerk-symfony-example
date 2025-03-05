<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequest;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequestOptions;

class ApplyClerkAuthSubscriber implements EventSubscriberInterface
{
    private string $clerkSecretKey;
    private array $clerkAuthorizedParties;

    public function __construct(string $clerkSecretKey, array $clerkAuthorizedParties)
    {
        $this->clerkSecretKey = $clerkSecretKey;
        $this->clerkAuthorizedParties = $clerkAuthorizedParties;

    }

    public static function getSubscribedEvents(): array
    {
        return [
           KernelEvents::CONTROLLER => [
                ['onKernelController', 1],
           ] 
        ];
        
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $requestState = AuthenticateRequest::authenticateRequest(
            $request,
            new AuthenticateRequestOptions(
                secretKey: $this->clerkSecretKey,
                authorizedParties: $this->clerkAuthorizedParties
            ),
        );
        if ($requestState->isSignedIn()){
            $request->attributes->set('verified_clerk_payload', $requestState->getPayload());
        } else {
            $request->attributes->set('verified_clerk_payload', null);
        }
    }
}
