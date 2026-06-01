<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Club\Entity\Sponsor;
use App\Domain\Club\Repository\SponsorRepositoryInterface;
use App\Entity\Sponsor as OrmSponsor;
use App\Infrastructure\Persistence\Doctrine\Mapper\SponsorMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSponsorRepository implements SponsorRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private SponsorMapper $mapper,
    ) {
    }

    public function save(Sponsor $sponsor): void
    {
        $existing = $this->em->getRepository(OrmSponsor::class)->find($sponsor->id());
        $orm = $this->mapper->toOrm($sponsor, $existing);

        $this->em->persist($orm);
        $this->em->flush();
    }

    public function remove(Sponsor $sponsor): void
    {
        $existing = $this->em->getRepository(OrmSponsor::class)->find($sponsor->id());
        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    public function findById(string $id): ?Sponsor
    {
        $orm = $this->em->getRepository(OrmSponsor::class)->find($id);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findAllActive(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('s')
            ->from(OrmSponsor::class, 's')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.sortOrder', 'ASC');

        return array_map(
            fn (OrmSponsor $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findAll(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('s')
            ->from(OrmSponsor::class, 's')
            ->orderBy('s.sortOrder', 'ASC');

        return array_map(
            fn (OrmSponsor $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }
}
