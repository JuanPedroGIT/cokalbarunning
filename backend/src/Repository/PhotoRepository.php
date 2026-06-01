<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Photo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    public function save(Photo $photo, bool $flush = true): void
    {
        $this->getEntityManager()->persist($photo);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Photo $photo, bool $flush = true): void
    {
        $this->getEntityManager()->remove($photo);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return Photo[] */
    public function findFeatured(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isFeatured = :featured')
            ->setParameter('featured', true)
            ->orderBy('p.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
