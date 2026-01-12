<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Controller\Admin;

use App\Inventory\Application\Command\ActivateWarehouseMessage;
use App\Inventory\Application\Command\CreateWarehouseMessage;
use App\Inventory\Application\Command\DeactivateWarehouseMessage;
use App\Inventory\Application\Dto\CreateWarehouseRequestDto;
use App\Inventory\Application\Query\GetWarehouseInfoQuery;
use App\Inventory\Application\Query\ListWarehousesQuery;
use App\Inventory\Domain\Model\Warehouse\Location;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\UuidV7;

#[Route('/api/admin/warehouses')]
#[IsGranted('ROLE_ADMIN')]
class WarehouseController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function create(
        #[MapRequestPayload]
        CreateWarehouseRequestDto $dto
    ): JsonResponse {
        $location = new Location(
            address: $dto->address,
            city: $dto->city,
            postalCode: $dto->postalCode,
            latitude: $dto->latitude,
            longitude: $dto->longitude
        );

        $message = new CreateWarehouseMessage(
            uuid: new UuidV7(), // todo: have a second look
            name: $dto->name,
            capacity: $dto->capacity,
            location: $location,
            type: $dto->type
        );

        $this->messageBus->dispatch($message);

        return new JsonResponse([
            'message' => 'Warehouse creation request accepted',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getInfo(string $id): JsonResponse
    {
        try {
            $dto = $this->handle(new GetWarehouseInfoQuery(new UuidV7($id)));

            return new JsonResponse($dto->toArray());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $dto = $this->handle(new ListWarehousesQuery());

        return new JsonResponse($dto->toArray());
    }

    #[Route('/{id}/activate', methods: ['PATCH'])]
    public function activate(string $id): JsonResponse
    {
        try {
            $this->messageBus->dispatch(new ActivateWarehouseMessage(new UuidV7($id)));

            return new JsonResponse(['message' => 'Warehouse activation request accepted'], Response::HTTP_ACCEPTED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/{id}/deactivate', methods: ['PATCH'])]
    public function deactivate(string $id): JsonResponse
    {
        try {
            $this->messageBus->dispatch(new DeactivateWarehouseMessage(new UuidV7($id)));

            return new JsonResponse(['message' => 'Warehouse deactivation request accepted'], Response::HTTP_ACCEPTED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
