<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\Persistence\Doctrine\Cache;

use App\Account\Domain\Model\User;
use App\Account\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDecorator(decorates: UserRepositoryInterface::class)]
class CachedUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private UserRepositoryInterface $decoratedRepository,
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function find(Uuid $id): ?User
    {
        $key = 'user_'.$id->toRfc4122();

        return $this->cache->get($key, function (ItemInterface $item) use ($id) {
            $item->tag(['user_id_'.$id->toRfc4122(), 'user_all']);
            $item->expiresAfter(120); // 2 minutes

            return $this->decoratedRepository->find($id);
        });
    }

    public function findByEmail(string $email): ?User
    {
        $key = 'user_email_'.md5($email);

        return $this->cache->get($key, function (ItemInterface $item) use ($email) {
            $item->tag(['user_email_'.md5($email)]);
            $item->expiresAfter(120); // 2 minutes

            return $this->decoratedRepository->findByEmail($email);
        });
    }

    public function findAll(): array
    {
        $key = 'user_all';

        return $this->cache->get($key, function (ItemInterface $item) {
            $item->tag(['user_all']);
            $item->expiresAfter(120); // 2 minutes

            return $this->decoratedRepository->findAll();
        });
    }

    public function save(User $user): void
    {
        $this->decoratedRepository->save($user);

        // Invalidate all user-related caches
        $tags = ['user_all'];
        if ($user->getUuid() !== null) {
            $tags[] = 'user_id_'.$user->getUuid()->toRfc4122();
            $tags[] = 'user_email_'.md5($user->email);
        }

        $this->cache->invalidateTags($tags);
    }

    public function findOneByReservationToken(string $token): ?User
    {
        // Never cache security-sensitive token lookups
        return $this->decoratedRepository->findOneByReservationToken($token);
    }
}
