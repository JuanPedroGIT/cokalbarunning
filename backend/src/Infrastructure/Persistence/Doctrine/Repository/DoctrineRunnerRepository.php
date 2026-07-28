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

    public function remove(Runner $runner): void
    {
        $existing = $this->em->getRepository(OrmRunner::class)->find($runner->id());
        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    public function findById(string $id): ?Runner
    {
        $orm = $this->em->getRepository(OrmRunner::class)->find($id);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByBibNumberAndEditionId(string $bibNumber, string $raceEditionId): ?Runner
    {
        $orm = $this->em->getRepository(OrmRunner::class)->findOneBy([
            'bibNumber' => $bibNumber,
            'raceEditionId' => $raceEditionId,
        ]);

        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByEmailAndEditionId(string $email, string $raceEditionId): ?Runner
    {
        $orm = $this->em->getRepository(OrmRunner::class)->findOneBy([
            'email' => $email,
            'raceEditionId' => $raceEditionId,
        ]);

        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByEditionId(string $raceEditionId): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('r')
            ->from(OrmRunner::class, 'r')
            ->where('r.raceEditionId = :editionId')
            ->setParameter('editionId', $raceEditionId)
            ->orderBy('r.bibNumber', 'ASC');

        return array_map(
            fn (OrmRunner $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findByEditionIdAndBibRange(string $raceEditionId, ?string $bibFrom, ?string $bibTo): array
    {
        $all = $this->findByEditionId($raceEditionId);

        // null = campo no enviado, "" = sin filtro. "0" es un valor de filtro válido.
        $hasFrom = $bibFrom !== null && $bibFrom !== '';
        $hasTo = $bibTo !== null && $bibTo !== '';

        if (!$hasFrom && !$hasTo) {
            return $all;
        }

        $from = $hasFrom ? (int) $bibFrom : null;
        $to = $hasTo ? (int) $bibTo : null;

        return array_values(array_filter($all, function (Runner $runner) use ($from, $to, $hasFrom, $hasTo): bool {
            $bib = $runner->bibNumber();
            $bibInt = ($bib !== null && $bib !== '' && trim($bib, '0') !== '') ? (int) $bib : 0;

            if ($hasFrom && $bibInt < $from) {
                return false;
            }
            if ($hasTo && $bibInt > $to) {
                return false;
            }
            return true;
        }));
    }

    public function searchByEditionId(string $raceEditionId, ?string $name, ?string $bib, int $maxResults): array
    {
        $qb = $this->em->getRepository(OrmRunner::class)
            ->createQueryBuilder('r')
            ->where('r.raceEditionId = :editionId')
            ->setParameter('editionId', $raceEditionId)
            ->andWhere('r.bibNumber IS NOT NULL');

        $hasFilter = false;

        if ($name !== null && $name !== '') {
            $term = mb_strtolower($name);
            $qb->andWhere(
                'LOWER(r.firstName) LIKE :name OR LOWER(r.lastName) LIKE :name OR LOWER(CONCAT(r.firstName, \' \', r.lastName)) LIKE :name'
            )
            ->setParameter('name', '%' . $term . '%');
            $hasFilter = true;
        }

        if ($bib !== null && $bib !== '') {
            $qb->andWhere('r.bibNumber LIKE :bib')
                ->setParameter('bib', '%' . $bib . '%');
            $hasFilter = true;
        }

        $qb
            ->orderBy('r.firstName', 'ASC')
            ->addOrderBy('r.lastName', 'ASC')
            ->setMaxResults($hasFilter ? $maxResults : 500);

        return array_map(
            fn (OrmRunner $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }
}
