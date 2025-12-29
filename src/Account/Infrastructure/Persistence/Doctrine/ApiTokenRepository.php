<?php

namespace App\Account\Infrastructure\Persistence\Doctrine;

use App\Account\Domain\Model\ApiToken;
use App\Account\Domain\Model\User;
use App\Account\Domain\Repository\ApiTokenRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ApiTokenRepository extends ServiceEntityRepository implements ApiTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function findByToken(string $token): ?ApiToken
    {
        $tokens = $this->findAll();
        return array_find($tokens, fn($apiToken) => $apiToken->verify($token));
    }

    public function findActiveTokens(User $user): array
    {
        return $this->createQueryBuilder('at')
            ->where('at.user = :user')
            ->andWhere('at.expiresAt IS NULL or at.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('at.createdAt')
            ->getQuery()
            ->getResult();
    }

    public function save(ApiToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }

    public function revoke(ApiToken $token): void
    {
        $this->getEntityManager()->remove($token);
        $this->getEntityManager()->flush();
    }

    public function revokeAllForUser(User $user): void
    {
        $this->createQueryBuilder('at')
            ->delete()
            ->where('at.user = :user')
            ->setParameter('user',$user)
            ->getQuery()
            ->execute();
    }

    public function revokeExpiredTokens(): int
    {
        return $this->createQueryBuilder('at')
            ->delete()
            ->where('at.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
