<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Race\Entity\RaceDocument;
use App\Domain\Race\Repository\RaceDocumentRepositoryInterface;
use App\Entity\RaceDocument as OrmRaceDocument;
use App\Entity\RaceEdition as OrmRaceEdition;
use App\Infrastructure\Persistence\Doctrine\Mapper\RaceDocumentMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRaceDocumentRepository implements RaceDocumentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RaceDocumentMapper $mapper,
    ) {
    }

    public function save(RaceDocument $document): void
    {
        $existing = $this->em->getRepository(OrmRaceDocument::class)->find($document->id());
        $orm = $this->mapper->toOrm($document, $existing);

        if ($document->editionId() !== null) {
            $edition = $this->em->getReference(OrmRaceEdition::class, $document->editionId());
            $orm->setEdition($edition);
        } else {
            $orm->setEdition(null);
        }

        if ($existing === null) {
            $orm->setCreatedAt($document->createdAt());
        }

        $this->em->persist($orm);
        $this->em->flush();
    }

    public function remove(RaceDocument $document): void
    {
        $existing = $this->em->getRepository(OrmRaceDocument::class)->find($document->id());
        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    public function findById(string $id): ?RaceDocument
    {
        $orm = $this->em->getRepository(OrmRaceDocument::class)->find($id);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByEditionId(string $editionId): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('d')
            ->from(OrmRaceDocument::class, 'd')
            ->where('d.edition = :editionId')
            ->setParameter('editionId', $editionId)
            ->orderBy('d.createdAt', 'DESC');

        return array_map(
            fn (OrmRaceDocument $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findGeneral(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('d')
            ->from(OrmRaceDocument::class, 'd')
            ->where('d.edition IS NULL')
            ->orderBy('d.createdAt', 'DESC');

        return array_map(
            fn (OrmRaceDocument $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findAll(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('d')
            ->from(OrmRaceDocument::class, 'd')
            ->orderBy('d.createdAt', 'DESC');

        return array_map(
            fn (OrmRaceDocument $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }
}
