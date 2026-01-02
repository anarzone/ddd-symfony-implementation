<?php

namespace App\Account\Infrastructure\Controller;

use App\Account\Application\Command\GenerateApiTokenMessage;
use App\Account\Application\Query\ListApiTokensQuery;
use App\Account\Domain\Repository\ApiTokenRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/tokens')]

class ApiTokenController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus,
        private ApiTokenRepositoryInterface $apiTokenRepository
    ) {
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function generate(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $message = new GenerateApiTokenMessage(
            user: $user,
            description: $data['description'] ?? null,
            expiresInDays: $data['expiresInDays'] ?? null
        );

        $envelope = $this->bus->dispatch($message);
        $handledStamp = $envelope->last(HandledStamp::class);

        if (!$handledStamp) {
            return new JsonResponse(['error' => 'Failed to generate token'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($handledStamp->getResult(), Response::HTTP_CREATED);
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $query = new ListApiTokensQuery(
            user: $user
        );

        $envelope = $this->bus->dispatch($query);
        $handledStamp = $envelope->last(HandledStamp::class);

        if (!$handledStamp) {
            return new JsonResponse(['error' => 'Failed to retrieve tokens'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($handledStamp->getResult());
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function revoke(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->apiTokenRepository->find($id);

        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], Response::HTTP_NOT_FOUND);
        }

        // Verify the token belongs to the current user
        if ($token->user->getId() !== $user->id) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->apiTokenRepository->revoke($token);

        return new JsonResponse(['message' => 'Token revoked successfully'], Response::HTTP_OK);
    }
}
