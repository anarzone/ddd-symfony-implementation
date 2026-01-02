<?php

namespace App\Inventory\Infrastructure\Controller;

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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReservationController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        private readonly Security   $security,
        private ValidatorInterface  $validator,
        private MessageBusInterface $messageBus,
    )
    {
    }

    #[Route('/api/reserve', methods: ['POST'])]
    public function reserve(
        #[MapRequestPayload] ReserveStockRequestDto $dto
    ): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $message = new ReserveStockMessage(
            $dto->stockId,
            $dto->quantity,
            $user->getId(),
            $dto->minutesValid
        );

        $this->messageBus->dispatch($message);

        return new JsonResponse([
            'message' => 'Reservation request added for processing',
            'data' => []
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/api/stock/{id}/level', methods: ['GET'])]
    public function getStockLevel(int $id): JsonResponse
    {
        return new JsonResponse(
            $this->handle(new GetStockLevelQuery($id))->toArray()
        );
    }
}
