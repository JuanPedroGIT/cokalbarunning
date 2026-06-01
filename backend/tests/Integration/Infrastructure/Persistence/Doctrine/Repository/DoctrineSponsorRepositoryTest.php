<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Club\Entity\Sponsor;
use App\Infrastructure\Persistence\Doctrine\Repository\DoctrineSponsorRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineSponsorRepositoryTest extends KernelTestCase
{
    private DoctrineSponsorRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(DoctrineSponsorRepository::class);
    }

    public function testSaveAndFindById(): void
    {
        $sponsor = new Sponsor(
            id: 'test-uuid-1',
            name: 'Integration Sponsor',
            logoUrl: 'https://example.com/logo.png',
            website: 'https://example.com',
            tier: 'gold',
            isActive: true,
            sortOrder: 5,
        );

        $this->repository->save($sponsor);

        $found = $this->repository->findById('test-uuid-1');

        self::assertNotNull($found);
        self::assertSame('Integration Sponsor', $found->name());
        self::assertSame('gold', $found->tier());
        self::assertSame(5, $found->sortOrder());
    }

    public function testFindByIdReturnsNullForMissing(): void
    {
        $found = $this->repository->findById('non-existent-id');
        self::assertNull($found);
    }

    public function testFindAllActiveReturnsOnlyActive(): void
    {
        $active = new Sponsor(id: 'active-1', name: 'Active Sponsor', isActive: true, sortOrder: 1);
        $inactive = new Sponsor(id: 'inactive-1', name: 'Inactive Sponsor', isActive: false, sortOrder: 2);

        $this->repository->save($active);
        $this->repository->save($inactive);

        $actives = $this->repository->findAllActive();
        $ids = array_map(fn (Sponsor $s) => $s->id(), $actives);

        self::assertContains('active-1', $ids);
        self::assertNotContains('inactive-1', $ids);
    }

    public function testFindAllReturnsAll(): void
    {
        $sponsor = new Sponsor(id: 'all-1', name: 'All Sponsor', isActive: true, sortOrder: 1);
        $this->repository->save($sponsor);

        $all = $this->repository->findAll();
        $ids = array_map(fn (Sponsor $s) => $s->id(), $all);

        self::assertContains('all-1', $ids);
    }

    public function testRemove(): void
    {
        $sponsor = new Sponsor(id: 'remove-1', name: 'To Remove', isActive: true, sortOrder: 1);
        $this->repository->save($sponsor);

        $found = $this->repository->findById('remove-1');
        self::assertNotNull($found);

        $this->repository->remove($found);

        $deleted = $this->repository->findById('remove-1');
        self::assertNull($deleted);
    }

    public function testUpdateExisting(): void
    {
        $sponsor = new Sponsor(id: 'update-1', name: 'Original', tier: 'bronze', sortOrder: 1);
        $this->repository->save($sponsor);

        $found = $this->repository->findById('update-1');
        $found->update('Updated', null, null, 'platinum', 99);
        $this->repository->save($found);

        $updated = $this->repository->findById('update-1');
        self::assertSame('Updated', $updated->name());
        self::assertSame('platinum', $updated->tier());
        self::assertSame(99, $updated->sortOrder());
    }
}
