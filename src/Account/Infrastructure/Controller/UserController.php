<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\Controller;

use App\Account\Application\Command\CreateUserMessage;
use App\Account\Application\Command\UpdateUserMessage;
use App\Account\Application\Dto\Request\CreateUserDto;
use App\Account\Application\Dto\Request\UpdateUserDto;
use App\Account\Application\Query\ListUsersQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\UuidV7;

#[Route('/api/users')]
class UserController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $bus
    ) {
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(#[MapRequestPayload] CreateUserDto $dto): JsonResponse
    {
        $uuid = new UuidV7();

        $message = new CreateUserMessage(
            uuid: $uuid,
            email: $dto->email,
            password: $dto->password,
            roles: $dto->roles,
        );

        $this->bus->dispatch($message);

        return new JsonResponse([
            'message' => 'New User being created',
            'data' => [
                'user' => [
                    'uuid' => $uuid,
                ],
            ],
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{uuid}', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(string $uuid, #[MapRequestPayload] UpdateUserDto $dto): JsonResponse
    {
        $uuid = new UuidV7($uuid);

        $message = new UpdateUserMessage(
            uuid: $uuid,
            email: $dto->email,
            roles: $dto->roles,
        );

        $this->bus->dispatch($message);

        return new JsonResponse([
            'message' => 'User being updated',
            'data' => [
                'user' => [
                    'uuid' => $uuid,
                ],
            ],
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function list(): JsonResponse
    {
        return new JsonResponse(
            $this->handle(new ListUsersQuery())
        );
    }
}
