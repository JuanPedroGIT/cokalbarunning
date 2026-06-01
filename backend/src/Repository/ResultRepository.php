<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Result;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Result::class);
    }

    public function save(Result $result, bool $flush = true): void
    {
        $this->getEntityManager()->persist($result);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function saveBulk(array $results, bool $flush = true): void
    {
        foreach ($results as $result) {
            $this->getEntityManager()->persist($result);
        }
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Result $result, bool $flush = true): void
    {
        $this->getEntityManager()->remove($result);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return Result[] */
    public function findByRaceEdition(string $raceEditionId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.raceEdition', 're')
            ->where('re.id = :id')
            ->setParameter('id', $raceEditionId)
            ->orderBy('r.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Result[] */
    public function findByRaceEditionYear(int $year): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.raceEdition', 're')
            ->where('re.year = :year')
            ->setParameter('year', $year)
            ->orderBy('r.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function clearPositionsForEdition(string $raceEditionId): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->update(Result::class, 'r')
            ->set('r.position', 'NULL')
            ->set('r.genderPosition', 'NULL')
            ->set('r.categoryPosition', 'NULL')
            ->innerJoin('r.raceEdition', 're')
            ->where('re.id = :id')
            ->setParameter('id', $raceEditionId)
            ->getQuery()
            ->execute();
    }
}
