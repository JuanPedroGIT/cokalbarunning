<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Results\Entity\Result;
use App\Domain\Results\Repository\ResultRepositoryInterface;
use App\Entity\Category as OrmCategory;
use App\Entity\RaceEdition as OrmRaceEdition;
use App\Entity\Result as OrmResult;
use App\Entity\Runner as OrmRunner;
use App\Infrastructure\Persistence\Doctrine\Mapper\ResultMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineResultRepository implements ResultRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ResultMapper $mapper,
    ) {
    }

    public function save(Result $result): void
    {
        $existing = $this->em->getRepository(OrmResult::class)->find($result->id());
        $orm = $this->mapper->toOrm($result, $existing);

        $raceEdition = $this->em->getReference(OrmRaceEdition::class, $result->raceEditionId()->value());
        $runner = $this->em->getReference(OrmRunner::class, $result->runner()->id());
        $category = $this->em->getReference(OrmCategory::class, $result->category()->id());

        $orm->setRaceEdition($raceEdition);
        $orm->setRunner($runner);
        $orm->setCategory($category);

        $this->em->persist($orm);
        $this->em->flush();
    }

    public function saveBulk(array $results): void
    {
        foreach ($results as $result) {
            $existing = $this->em->getRepository(OrmResult::class)->find($result->id());
            $orm = $this->mapper->toOrm($result, $existing);

            $raceEdition = $this->em->getReference(OrmRaceEdition::class, $result->raceEditionId()->value());
            $runner = $this->em->getReference(OrmRunner::class, $result->runner()->id());
            $category = $this->em->getReference(OrmCategory::class, $result->category()->id());

            $orm->setRaceEdition($raceEdition);
            $orm->setRunner($runner);
            $orm->setCategory($category);

            $this->em->persist($orm);
        }

        $this->em->flush();
    }

    public function remove(Result $result): void
    {
        $existing = $this->em->getRepository(OrmResult::class)->find($result->id());
        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    public function findById(string $id): ?Result
    {
        $orm = $this->em->getRepository(OrmResult::class)->find($id);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByRaceEdition(RaceEditionId $raceEditionId): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('r')
            ->from(OrmResult::class, 'r')
            ->innerJoin('r.raceEdition', 're')
            ->where('re.id = :id')
            ->setParameter('id', $raceEditionId->value())
            ->orderBy('r.position', 'ASC');

        return array_map(
            fn (OrmResult $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findByRaceEditionAndCategory(RaceEditionId $raceEditionId, string $categoryId): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('r')
            ->from(OrmResult::class, 'r')
            ->innerJoin('r.raceEdition', 're')
            ->innerJoin('r.category', 'c')
            ->where('re.id = :editionId')
            ->andWhere('c.id = :categoryId')
            ->setParameter('editionId', $raceEditionId->value())
            ->setParameter('categoryId', $categoryId)
            ->orderBy('r.position', 'ASC');

        return array_map(
            fn (OrmResult $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findByRaceEditionAndGender(RaceEditionId $raceEditionId, string $gender): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('r')
            ->from(OrmResult::class, 'r')
            ->innerJoin('r.raceEdition', 're')
            ->innerJoin('r.runner', 'ru')
            ->where('re.id = :editionId')
            ->andWhere('ru.gender = :gender')
            ->setParameter('editionId', $raceEditionId->value())
            ->setParameter('gender', $gender)
            ->orderBy('r.position', 'ASC');

        return array_map(
            fn (OrmResult $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function clearPositionsForEdition(RaceEditionId $raceEditionId): void
    {
        $this->em->createQueryBuilder()
            ->update(OrmResult::class, 'r')
            ->set('r.position', 'NULL')
            ->set('r.genderPosition', 'NULL')
            ->set('r.categoryPosition', 'NULL')
            ->where('r.raceEdition = :id')
            ->setParameter('id', $raceEditionId->value())
            ->getQuery()
            ->execute();
    }
}
