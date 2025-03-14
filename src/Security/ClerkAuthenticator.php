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
            error_log('Auth header: ' . ($authHeader ? substr($authHeader, 0, 20) . '...' : 'none'));
            
            
            $requestState = AuthenticateRequest::authenticateRequest(
                $request,
                new AuthenticateRequestOptions(
                    secretKey: $this->secretKey,
                    authorizedParties: $this->authorizedParties
                )
            );
            
            error_log('Request state: ' . ($requestState ? 'valid' : 'invalid'));
            
            
            if ($requestState && $requestState->isSignedIn()) {
                $payload = $requestState->getPayload();
                error_log('User authenticated: ' . $payload->sub);
                
                
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
            
            
            error_log('Authentication failed: Not signed in');
            throw new AuthenticationException('Invalid credentials');
        } catch (\Exception $e) {
            error_log('Authentication error: ' . $e->getMessage());
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