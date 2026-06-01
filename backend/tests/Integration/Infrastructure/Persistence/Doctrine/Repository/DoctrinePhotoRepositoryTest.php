<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Media\Entity\Photo;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Entity\RaceEdition as OrmRaceEdition;
use App\Infrastructure\Persistence\Doctrine\Repository\DoctrinePhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrinePhotoRepositoryTest extends KernelTestCase
{
    private DoctrinePhotoRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(DoctrinePhotoRepository::class);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSaveAndFindById(): void
    {
        $photo = new Photo(
            id: 'photo-test-1',
            originalPath: 'https://example.com/photo1.jpg',
            altText: 'Test photo',
            isFeatured: true,
            sortOrder: 1,
        );

        $this->repository->save($photo);

        $found = $this->repository->findById('photo-test-1');

        self::assertNotNull($found);
        self::assertSame('https://example.com/photo1.jpg', $found->originalPath());
        self::assertSame('Test photo', $found->altText());
        self::assertTrue($found->isFeatured());
    }

    public function testFindByIdReturnsNullForMissing(): void
    {
        $found = $this->repository->findById('non-existent-photo');
        self::assertNull($found);
    }

    public function testFindFeaturedReturnsOnlyFeatured(): void
    {
        $featured = new Photo(id: 'featured-1', originalPath: 'f1.jpg', isFeatured: true, sortOrder: 1);
        $normal = new Photo(id: 'normal-1', originalPath: 'n1.jpg', isFeatured: false, sortOrder: 2);

        $this->repository->save($featured);
        $this->repository->save($normal);

        $result = $this->repository->findFeatured();
        $ids = array_map(fn (Photo $p) => $p->id(), $result);

        self::assertContains('featured-1', $ids);
        self::assertNotContains('normal-1', $ids);
    }

    public function testFindAllReturnsAll(): void
    {
        $photo = new Photo(id: 'all-1', originalPath: 'all.jpg', isFeatured: false, sortOrder: 1);
        $this->repository->save($photo);

        $all = $this->repository->findAll();
        $ids = array_map(fn (Photo $p) => $p->id(), $all);

        self::assertContains('all-1', $ids);
    }

    public function testRemove(): void
    {
        $photo = new Photo(id: 'remove-1', originalPath: 'del.jpg', isFeatured: true, sortOrder: 1);
        $this->repository->save($photo);

        $found = $this->repository->findById('remove-1');
        self::assertNotNull($found);

        $this->repository->remove($found);

        $deleted = $this->repository->findById('remove-1');
        self::assertNull($deleted);
    }

    public function testSaveWithRaceEditionId(): void
    {
        $editionId = Uuid::uuid4()->toString();
        $edition = new OrmRaceEdition();
        $edition->setId($editionId);
        $edition->setYear(2025);
        $edition->setName('Test Edition');
        $edition->setDescription('Test');
        $edition->setDate(new \DateTimeImmutable('2025-07-01'));
        $edition->setLocation('Coca de Alba');
        $this->em->persist($edition);
        $this->em->flush();

        $photo = new Photo(
            id: 'photo-race-1',
            originalPath: 'race.jpg',
            raceEditionId: RaceEditionId::fromString($editionId),
            isFeatured: false,
            sortOrder: 1,
        );

        $this->repository->save($photo);

        $found = $this->repository->findById('photo-race-1');
        self::assertNotNull($found);
        self::assertNotNull($found->raceEditionId());
        self::assertSame($editionId, $found->raceEditionId()->value());
    }
}
