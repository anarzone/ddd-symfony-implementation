<?php

namespace App\Account\Infrastructure\Controller;

use App\Account\Application\Command\CreateUserMessage;
use App\Account\Application\Query\ListUsersQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus
    ) {
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Email and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $message = new CreateUserMessage(
                email: $data['email'],
                password: $data['password'],
                roles: $data['roles'] ?? ['ROLE_USER']
            );

            $envelope = $this->bus->dispatch($message);
            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                return new JsonResponse([
                    'error' => 'Failed to create user'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return new JsonResponse($handledStamp->getResult(), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(): JsonResponse
    {
        $query = new ListUsersQuery();

        $envelope = $this->bus->dispatch($query);
        $handledStamp = $envelope->last(HandledStamp::class);

        if (!$handledStamp) {
            return new JsonResponse([
                'error' => 'Failed to retrieve users'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($handledStamp->getResult());
    }
}
