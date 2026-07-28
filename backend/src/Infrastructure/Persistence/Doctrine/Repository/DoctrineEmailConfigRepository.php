<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Notification\Entity\EmailConfig;
use App\Domain\Notification\Repository\EmailConfigRepositoryInterface;
use App\Entity\EmailConfig as OrmEmailConfig;
use App\Infrastructure\Persistence\Doctrine\Mapper\EmailConfigMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEmailConfigRepository implements EmailConfigRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailConfigMapper $mapper,
    ) {
    }

    public function save(EmailConfig $config): void
    {
        $existing = $this->em->getRepository(OrmEmailConfig::class)->find($config->id());
        $orm = $this->mapper->toOrm($config, $existing);

        $this->em->persist($orm);
        $this->em->flush();
    }

    public function remove(EmailConfig $config): void
    {
        $existing = $this->em->getRepository(OrmEmailConfig::class)->find($config->id());
        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    public function findById(string $id): ?EmailConfig
    {
        $orm = $this->em->getRepository(OrmEmailConfig::class)->find($id);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByRaceEditionIdAndType(string $raceEditionId, string $type): ?EmailConfig
    {
        $orm = $this->em->getRepository(OrmEmailConfig::class)->findOneBy([
            'raceEditionId' => $raceEditionId,
            'type' => $type,
        ]);

        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByRaceEditionId(string $raceEditionId): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
            ->from(OrmEmailConfig::class, 'c')
            ->where('c.raceEditionId = :editionId')
            ->setParameter('editionId', $raceEditionId)
            ->orderBy('c.type', 'ASC');

        return array_map(
            fn (OrmEmailConfig $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findAll(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
            ->from(OrmEmailConfig::class, 'c')
            ->orderBy('c.raceEditionId', 'ASC')
            ->addOrderBy('c.type', 'ASC');

        return array_map(
            fn (OrmEmailConfig $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }
}
