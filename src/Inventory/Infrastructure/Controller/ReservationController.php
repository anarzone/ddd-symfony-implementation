<?php

namespace App\Inventory\Infrastructure\Controller;

use App\Inventory\Application\Command\ReserveStockMessage;
use App\Inventory\Application\Query\GetStockLevelQuery;
use App\Inventory\Domain\Model\Stock\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    public function __construct(
        private Security $security
    ) {}

    #[Route('/api/reserve', methods: ['POST'])]
    public function reserve(Request $request, MessageBusInterface $bus): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Get authenticated user
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        // Create message with user ID
        $message = new ReserveStockMessage(
            stockId: $data['stockId'] ?? 0,
            quantity: $data['quantity'] ?? 0,
            userId: $user->id,
            minutesValid: $data['minutesValid'] ?? 15
        );

        try {
            $result = $bus->dispatch($message);

            return new JsonResponse([
                'message' => 'Reservation created successfully',
                'data' => $result
            ], Response::HTTP_ACCEPTED);
        } catch (HandlerFailedException $e) {
            $previousException = $e->getPrevious();
            $errorMessage = $previousException?->getMessage() ?? 'Reservation failed';

            return new JsonResponse([
                'error' => $errorMessage
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/stock/{id}/level', methods: ['GET'])]
    public function getStockLevel(int $id, MessageBusInterface $bus): JsonResponse
    {
        $query = new GetStockLevelQuery($id);

        try {
            $dto = $bus->dispatch($query);

            return new JsonResponse($dto->toArray());
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/debug/n-plus-one', methods: ['GET'])]
    public function debugNPlusOne(EntityManagerInterface $em): JsonResponse
    {
        // 1. Fetch ALL stocks (e.g., 1,000 rows)
        $stocks = $em->getRepository(Stock::class)->findAllWithWarehouse();

        $data = [];
        foreach ($stocks as $stock) {
            // 2. THE TRAP: accessing the Warehouse relation inside the loop
            // Since we used findAll(), the warehouse is a "Proxy".
            // Doctrine executes a NEW SQL query here for every single stock.
            $warehouseName = $stock->warehouse->name;

            $data[] = [
                'sku' => $stock->sku->code,
                'warehouse' => $warehouseName
            ];
        }

        return $this->json($data);
    }
}
