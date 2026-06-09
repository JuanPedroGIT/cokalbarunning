<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\SocialPublishing\Entity\SocialPublishLog;
use App\Domain\SocialPublishing\Repository\SocialPublishLogRepositoryInterface;
use App\Entity\SocialPublishLog as OrmSocialPublishLog;
use App\Infrastructure\Persistence\Doctrine\Mapper\SocialPublishLogMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSocialPublishLogRepository implements SocialPublishLogRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private SocialPublishLogMapper $mapper,
    ) {
    }

    public function save(SocialPublishLog $log): void
    {
        $existing = $this->em->getRepository(OrmSocialPublishLog::class)->find($log->id());
        $orm = $this->mapper->toOrm($log, $existing);
        $this->em->persist($orm);
        $this->em->flush();
    }

    public function findById(string $id): ?SocialPublishLog
    {
        $orm = $this->em->getRepository(OrmSocialPublishLog::class)->find($id);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByPostAndNetwork(string $postId, string $network): ?SocialPublishLog
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('l')
            ->from(OrmSocialPublishLog::class, 'l')
            ->where('l.postId = :postId')
            ->andWhere('l.network = :network')
            ->setParameter('postId', $postId)
            ->setParameter('network', $network)
            ->setMaxResults(1);

        $orm = $qb->getQuery()->getOneOrNullResult();

        return $orm !== null ? $this->mapper->toDomain($orm) : null;
    }

    public function findByPost(string $postId): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('l')
            ->from(OrmSocialPublishLog::class, 'l')
            ->where('l.postId = :postId')
            ->setParameter('postId', $postId)
            ->orderBy('l.createdAt', 'DESC');

        return array_map(
            fn (OrmSocialPublishLog $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findAll(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('l')
            ->from(OrmSocialPublishLog::class, 'l')
            ->orderBy('l.createdAt', 'DESC');

        return array_map(
            fn (OrmSocialPublishLog $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }
}
