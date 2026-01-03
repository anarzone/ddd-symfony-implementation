<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Controller;

use App\Account\Domain\Model\User;
use App\Inventory\Application\Command\ReserveStockMessage;
use App\Inventory\Application\Dto\ReserveStockRequestDto;
use App\Inventory\Application\Query\GetStockLevelQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        private readonly Security $security,
        private MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/api/reserve', methods: ['POST'])]
    public function reserve(
        #[MapRequestPayload]
        ReserveStockRequestDto $dto
    ): JsonResponse {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $userId = $user->getId();
        if ($userId === null) {
            return new JsonResponse(['error' => 'Invalid user ID'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Convert Uuid to UuidV7
        $userIdV7 = new \Symfony\Component\Uid\UuidV7($userId->toRfc4122());

        $message = new ReserveStockMessage(
            $dto->stockId,
            $dto->quantity,
            $userIdV7,
            $dto->minutesValid
        );

        $this->messageBus->dispatch($message);

        return new JsonResponse([
            'message' => 'Reservation request added for processing',
            'data' => [],
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/api/stock/{id}/level', methods: ['GET'])]
    public function getStockLevel(string $id): JsonResponse
    {
        $uuid = new \Symfony\Component\Uid\UuidV7($id);

        return new JsonResponse(
            $this->handle(new GetStockLevelQuery($uuid))->toArray()
        );
    }
}
