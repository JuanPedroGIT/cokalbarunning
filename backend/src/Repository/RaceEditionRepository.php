<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RaceEdition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RaceEditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RaceEdition::class);
    }

    public function save(RaceEdition $raceEdition, bool $flush = true): void
    {
        $this->getEntityManager()->persist($raceEdition);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RaceEdition $raceEdition, bool $flush = true): void
    {
        $this->getEntityManager()->remove($raceEdition);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActive(): ?RaceEdition
    {
        return $this->createQueryBuilder('r')
            ->where('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.year', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return RaceEdition[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.year', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
