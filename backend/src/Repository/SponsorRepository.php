<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Sponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

    public function save(Sponsor $sponsor, bool $flush = true): void
    {
        $this->getEntityManager()->persist($sponsor);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Sponsor $sponsor, bool $flush = true): void
    {
        $this->getEntityManager()->remove($sponsor);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return Sponsor[] */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
