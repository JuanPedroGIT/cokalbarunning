<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Race\Entity\RaceEdition;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Entity\RaceEdition as OrmRaceEdition;
use App\Infrastructure\Persistence\Doctrine\Mapper\RaceEditionMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRaceEditionRepository implements RaceEditionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RaceEditionMapper $mapper,
    ) {
    }

    public function save(RaceEdition $raceEdition): void
    {
        $existing = $this->em->getRepository(OrmRaceEdition::class)->find($raceEdition->id()->value());
        $orm = $this->mapper->toOrm($raceEdition, $existing);
        $this->em->persist($orm);
        $this->em->flush();
    }

    public function remove(RaceEdition $raceEdition): void
    {
        $existing = $this->em->getRepository(OrmRaceEdition::class)->find($raceEdition->id()->value());
        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    public function findById(RaceEditionId $id): ?RaceEdition
    {
        $orm = $this->em->getRepository(OrmRaceEdition::class)->find($id->value());
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByYear(EditionYear $year): ?RaceEdition
    {
        $orm = $this->em->getRepository(OrmRaceEdition::class)->findOneBy(['year' => $year->value()]);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findActive(): ?RaceEdition
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('r')
            ->from(OrmRaceEdition::class, 'r')
            ->where('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.year', 'DESC')
            ->setMaxResults(1);

        $orm = $qb->getQuery()->getOneOrNullResult();
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findAllOrdered(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('r')
            ->from(OrmRaceEdition::class, 'r')
            ->orderBy('r.year', 'DESC');

        return array_map(
            fn (OrmRaceEdition $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }
}
