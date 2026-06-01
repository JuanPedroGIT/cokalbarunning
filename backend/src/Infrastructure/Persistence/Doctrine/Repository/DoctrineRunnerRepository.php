<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Registration\Entity\Runner;
use App\Domain\Registration\Repository\RunnerRepositoryInterface;
use App\Entity\Runner as OrmRunner;
use App\Infrastructure\Persistence\Doctrine\Mapper\RunnerMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRunnerRepository implements RunnerRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RunnerMapper $mapper,
    ) {
    }

    public function save(Runner $runner): void
    {
        $existing = $this->em->getRepository(OrmRunner::class)->find($runner->id());
        $orm = $this->mapper->toOrm($runner, $existing);
        $this->em->persist($orm);
        $this->em->flush();
    }

    public function findById(string $id): ?Runner
    {
        $orm = $this->em->getRepository(OrmRunner::class)->find($id);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByEmail(string $email): ?Runner
    {
        $orm = $this->em->getRepository(OrmRunner::class)->findOneBy(['email' => $email]);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }
}
