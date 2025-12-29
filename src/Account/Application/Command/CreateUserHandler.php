<?php

namespace App\Account\Application\Command;

use App\Account\Domain\Model\User;
use App\Account\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
class CreateUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function __invoke(CreateUserMessage $message): array
    {
        // Check if user already exists
        $existingUser = $this->userRepository->findByEmail($message->email);
        if ($existingUser) {
            throw new \InvalidArgumentException('User with this email already exists');
        }

        // Create new user
        $user = new User($message->email, $message->roles);
        $user->password = $this->passwordHasher->hashPassword($user, $message->password);

        $this->userRepository->save($user);

        return [
            'id' => $user->id,
            'email' => $user->email,
            'roles' => $user->getRoles(),
            'createdAt' => $user->createdAt->format(\DateTime::ATOM)
        ];
    }
}
