<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Race\Entity\RaceEdition;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Infrastructure\Persistence\Doctrine\Repository\DoctrineRaceEditionRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineRaceEditionRepositoryTest extends KernelTestCase
{
    private DoctrineRaceEditionRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(DoctrineRaceEditionRepository::class);
    }

    public function testSaveAndFindById(): void
    {
        $id = RaceEditionId::generate();
        $edition = new RaceEdition(
            id: $id,
            year: EditionYear::fromInt(2025),
            name: 'Integration Race',
            description: 'Test description',
            date: new \DateTimeImmutable('2025-07-01'),
            location: 'Coca de Alba',
            isActive: true,
            posterUrl: 'poster.jpg',
            registrationUrl: 'https://register.com',
        );

        $this->repository->save($edition);

        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame('Integration Race', $found->name());
        self::assertSame(2025, $found->year()->value());
        self::assertSame('poster.jpg', $found->posterUrl());
    }

    public function testFindByIdReturnsNullForMissing(): void
    {
        $found = $this->repository->findById(RaceEditionId::generate());
        self::assertNull($found);
    }

    public function testFindByYear(): void
    {
        $edition = new RaceEdition(
            id: RaceEditionId::generate(),
            year: EditionYear::fromInt(2024),
            name: 'Year Test',
            description: 'Test',
            date: new \DateTimeImmutable('2024-07-01'),
            location: 'Coca de Alba',
        );

        $this->repository->save($edition);

        $found = $this->repository->findByYear(EditionYear::fromInt(2024));
        self::assertNotNull($found);
        self::assertSame('Year Test', $found->name());
    }

    public function testFindActiveReturnsMostRecentActive(): void
    {
        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $existing2028 = $em->getRepository(\App\Entity\RaceEdition::class)->findBy(['year' => 2028]);
        foreach ($existing2028 as $e) {
            $em->remove($e);
        }
        $em->flush();

        $older = new RaceEdition(
            id: RaceEditionId::generate(),
            year: EditionYear::fromInt(2023),
            name: 'Old Race',
            description: 'Test',
            date: new \DateTimeImmutable('2023-07-01'),
            location: 'Coca de Alba',
            isActive: true,
        );
        $newer = new RaceEdition(
            id: RaceEditionId::generate(),
            year: EditionYear::fromInt(2028),
            name: 'New Race',
            description: 'Test',
            date: new \DateTimeImmutable('2028-07-01'),
            location: 'Coca de Alba',
            isActive: true,
        );

        $this->repository->save($older);
        $this->repository->save($newer);

        $active = $this->repository->findActive();
        self::assertNotNull($active);
        self::assertSame(2028, $active->year()->value());
        self::assertSame('New Race', $active->name());
    }

    public function testFindAllOrdered(): void
    {
        $edition = new RaceEdition(
            id: RaceEditionId::generate(),
            year: EditionYear::fromInt(2026),
            name: 'Ordered Race',
            description: 'Test',
            date: new \DateTimeImmutable('2026-07-01'),
            location: 'Coca de Alba',
        );

        $this->repository->save($edition);

        $all = $this->repository->findAllOrdered();
        $years = array_map(fn (RaceEdition $e) => $e->year()->value(), $all);

        self::assertContains(2026, $years);
    }

    public function testRemove(): void
    {
        $id = RaceEditionId::generate();
        $edition = new RaceEdition(
            id: $id,
            year: EditionYear::fromInt(2022),
            name: 'To Remove',
            description: 'Test',
            date: new \DateTimeImmutable('2022-07-01'),
            location: 'Coca de Alba',
        );

        $this->repository->save($edition);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);

        $this->repository->remove($found);

        $deleted = $this->repository->findById($id);
        self::assertNull($deleted);
    }
}
