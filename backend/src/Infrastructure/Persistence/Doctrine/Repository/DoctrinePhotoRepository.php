<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Media\Entity\Photo;
use App\Domain\Media\Repository\PhotoRepositoryInterface;
use App\Entity\Photo as OrmPhoto;
use App\Entity\RaceEdition as OrmRaceEdition;
use App\Infrastructure\Persistence\Doctrine\Mapper\PhotoMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePhotoRepository implements PhotoRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private PhotoMapper $mapper,
    ) {
    }

    public function save(Photo $photo): void
    {
        $existing = $this->em->getRepository(OrmPhoto::class)->find($photo->id());
        $orm = $this->mapper->toOrm($photo, $existing);

        if ($photo->raceEditionId() !== null) {
            $raceEdition = $this->em->getReference(OrmRaceEdition::class, $photo->raceEditionId()->value());
            $orm->setRaceEdition($raceEdition);
        } else {
            $orm->setRaceEdition(null);
        }

        $this->em->persist($orm);
        $this->em->flush();
    }

    public function remove(Photo $photo): void
    {
        $existing = $this->em->getRepository(OrmPhoto::class)->find($photo->id());
        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    public function findById(string $id): ?Photo
    {
        $orm = $this->em->getRepository(OrmPhoto::class)->find($id);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findFeatured(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
            ->from(OrmPhoto::class, 'p')
            ->where('p.isFeatured = :featured')
            ->setParameter('featured', true)
            ->orderBy('p.sortOrder', 'ASC');

        return array_map(
            fn (OrmPhoto $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findAll(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
            ->from(OrmPhoto::class, 'p')
            ->orderBy('p.sortOrder', 'ASC');

        return array_map(
            fn (OrmPhoto $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findByEditionId(string $editionId): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
            ->from(OrmPhoto::class, 'p')
            ->where('p.raceEdition = :editionId')
            ->setParameter('editionId', $editionId)
            ->orderBy('p.sortOrder', 'ASC');

        return array_map(
            fn (OrmPhoto $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }
}
