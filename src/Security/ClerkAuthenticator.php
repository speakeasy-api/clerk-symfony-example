<?php

namespace App\Security;

use Clerk\Backend\Helpers\Jwks\AuthenticateRequest;
use Clerk\Backend\Helpers\Jwks\AuthenticateRequestOptions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;


class ClerkAuthenticator extends AbstractAuthenticator implements AuthenticationFailureHandlerInterface
{
    private string $secretKey;
    private array $authorizedParties;

    public function __construct(string $secretKey, string $authorizedParties)
    {
        $this->secretKey = $secretKey;
        $this->authorizedParties = explode(',', $authorizedParties);
    }

    
    public function supports(Request $request): ?bool
    {
        
        if ($request->getMethod() === 'OPTIONS') {
            return false;
        }

        return $request->headers->has('Authorization');
    }

    
    public function authenticate(Request $request): Passport
    {
        try {
            
            $authHeader = $request->headers->get('Authorization');
            $requestState = AuthenticateRequest::authenticateRequest(
                $request,
                new AuthenticateRequestOptions(
                    secretKey: $this->secretKey,
                    authorizedParties: $this->authorizedParties
                )
            ); 

            if ($requestState && $requestState->isSignedIn()) {
                $payload = $requestState->getPayload(); 
                
                return new SelfValidatingPassport(
                    new UserBadge($payload->sub, function (string $userIdentifier) {
                        
                        return new class($userIdentifier) implements \Symfony\Component\Security\Core\User\UserInterface {
                            private string $identifier;
                            
                            public function __construct(string $identifier) {
                                $this->identifier = $identifier;
                            }
                            
                            public function getRoles(): array {
                                return ['ROLE_USER'];
                            }
                            
                            public function getUserIdentifier(): string {
                                return $this->identifier;
                            }
                            
                            public function eraseCredentials(): void {
                                
                            }
                        };
                    })
                );
            }
            
            // Include error reason in the exception message if available
            $errorReason = $requestState && method_exists($requestState, 'getErrorReason') 
                ? $requestState->getErrorReason() 
                : 'User not signed in';
                
            throw new AuthenticationException('Invalid credentials: ' . $errorReason);
        } catch (\Exception $e) {
            throw new AuthenticationException('Authentication error: ' . $e->getMessage());
        }
    }

    
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        
        return null;
    }

    
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $data = ['message' => $exception->getMessage()];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
} 