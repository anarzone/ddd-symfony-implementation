<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\Controller;

use App\Account\Application\Command\GenerateApiTokenMessage;
use App\Account\Domain\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MessageBusInterface $bus
    ) {
    }

    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Email and password are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user) {
            return new JsonResponse([
                'error' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'error' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Generate API token automatically
        $message = new GenerateApiTokenMessage(
            user: $user,
            description: $data['description'] ?? 'Created via login',
            expiresInDays: $data['expiresInDays'] ?? 30
        );

        $envelope = $this->bus->dispatch($message);
        $handledStamp = $envelope->last(HandledStamp::class);

        if (!$handledStamp) {
            return new JsonResponse([
                'error' => 'Failed to generate token',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $tokenData = $handledStamp->getResult();

        return new JsonResponse([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
            'token' => $tokenData['token'],
            'tokenId' => $tokenData['tokenId'],
        ], Response::HTTP_OK);
    }
}
