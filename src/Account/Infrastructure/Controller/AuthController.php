<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\Controller;

use App\Account\Application\Dto\Request\LoginRequestDto;
use App\Account\Application\Query\LoginQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth')]
class AuthController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/login', methods: ['POST'])]
    public function login(
        #[MapRequestPayload]
        LoginRequestDto $dto
    ): JsonResponse {
        $result = $this->handle(new LoginQuery($dto));

        return new JsonResponse([
            'message' => 'Login successful',
            ...$result,
        ], Response::HTTP_OK);
    }
}
