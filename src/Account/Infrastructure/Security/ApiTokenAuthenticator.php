<?php

namespace App\Account\Infrastructure\Security;

use App\Account\Domain\Repository\ApiTokenRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{

    public function __construct(
        private readonly ApiTokenRepositoryInterface $apiTokenRepository
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('Authorization');

        if (!$apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        if (str_starts_with($apiToken, 'Bearer ')) {
            $apiToken = substr($apiToken, 7);
        }

        $token = $this->apiTokenRepository->findByToken($apiToken);

        if (!$token) {
            throw new CustomUserMessageAuthenticationException('Invalid API token');
        }

        if (!$token->isValid()) {
            throw new CustomUserMessageAuthenticationException('Token expired or revoked');
        }

        $token->markAsUsed();
        $this->apiTokenRepository->save($token);

        $user = $token->user;

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn() => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'error' => 'Authentication failed',
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
