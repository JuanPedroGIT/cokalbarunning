<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Runner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RunnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Runner::class);
    }

    public function save(Runner $runner, bool $flush = true): void
    {
        $this->getEntityManager()->persist($runner);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
