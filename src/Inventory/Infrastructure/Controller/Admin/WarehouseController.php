<?php

namespace App\Inventory\Infrastructure\Controller\Admin;

use App\Inventory\Application\Command\CreateWarehouseMessage;
use App\Inventory\Application\Dto\WarehouseInfoDto;
use App\Inventory\Domain\Model\Stock\Stock;
use App\Inventory\Domain\Repository\WarehouseRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/warehouses')]
#[IsGranted('ROLE_ADMIN')]
class WarehouseController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus,
        private WarehouseRepositoryInterface $warehouseRepository
    ) {}

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $message = new CreateWarehouseMessage(
            name: $data['name'] ?? '',
            capacity: $data['capacity'] ?? 0,
            address: $data['address'] ?? '',
            city: $data['city'] ?? '',
            postalCode: $data['postalCode'] ?? '',
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            type: $data['type'] ?? 'STANDARD'
        );

        try {
            $result = $this->bus->dispatch($message);

            return new JsonResponse([
                'message' => 'Warehouse created successfully',
                'data' => $result
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getInfo(int $id): JsonResponse
    {
        $warehouse = $this->warehouseRepository->find($id);

        if (!$warehouse) {
            return new JsonResponse(['error' => 'Warehouse not found'], Response::HTTP_NOT_FOUND);
        }

        $dto = new WarehouseInfoDto(
            id: $warehouse->getId(),
            name: $warehouse->name,
            capacity: $warehouse->capacity,
            currentStock: 0,
            isActive: $warehouse->isOpen(),
            type: $warehouse->type,
            address: $warehouse->location->address,
            city: $warehouse->location->city,
            postalCode: $warehouse->location->postalCode,
            latitude: $warehouse->location->latitude,
            longitude: $warehouse->location->longitude
        );

        return new JsonResponse($dto->toArray());
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $warehouses = $this->warehouseRepository->findAll();

        $dtos = array_map(fn($w) => new WarehouseInfoDto(
            id: $w->getId(),
            name: $w->name,
            capacity: $w->capacity,
            currentStock: 0,
            isActive: $w->isOpen(),
            type: $w->type,
            address: $w->location->address,
            city: $w->location->city,
            postalCode: $w->location->postalCode,
            latitude: $w->location->latitude,
            longitude: $w->location->longitude
        ), $warehouses);

        return new JsonResponse(array_map(fn($dto) => $dto->toArray(), $dtos));
    }

    #[Route('/{id}/activate', methods: ['PATCH'])]
    public function activate(int $id): JsonResponse
    {
        $warehouse = $this->warehouseRepository->findWithLock($id);

        if (!$warehouse) {
            return new JsonResponse(['error' => 'Warehouse not found'], Response::HTTP_NOT_FOUND);
        }

        $reflection = new \ReflectionClass($warehouse);
        $property = $reflection->getProperty('isActive');
        $property->setAccessible(true);
        $property->setValue($warehouse, true);

        $this->warehouseRepository->save($warehouse);

        return new JsonResponse(['message' => 'Warehouse activated']);
    }

    #[Route('/{id}/deactivate', methods: ['PATCH'])]
    public function deactivate(int $id): JsonResponse
    {
        $warehouse = $this->warehouseRepository->findWithLock($id);

        if (!$warehouse) {
            return new JsonResponse(['error' => 'Warehouse not found'], Response::HTTP_NOT_FOUND);
        }

        $reflection = new \ReflectionClass($warehouse);
        $property = $reflection->getProperty('isActive');
        $property->setAccessible(true);
        $property->setValue($warehouse, false);

        $this->warehouseRepository->save($warehouse);

        return new JsonResponse(['message' => 'Warehouse deactivated']);
    }
}
