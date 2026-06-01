<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Race\Entity\Category as DomainCategory;
use App\Domain\Race\ValueObject\Distance;
use App\Entity\Category as OrmCategory;
use App\Infrastructure\Persistence\Doctrine\Mapper\CategoryMapper;
use PHPUnit\Framework\TestCase;

final class CategoryMapperTest extends TestCase
{
    private CategoryMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new CategoryMapper();
    }

    public function testToDomainMapsAllFields(): void
    {
        $orm = new OrmCategory();
        $orm->setId('cat-1');
        $orm->setName('Senior Masculino');
        $orm->setMinAge(18);
        $orm->setMaxAge(35);
        $orm->setDistanceKm(10.5);
        $orm->setGender('M');

        $domain = $this->mapper->toDomain($orm);

        self::assertSame('cat-1', $domain->id());
        self::assertSame('Senior Masculino', $domain->name());
        self::assertSame(18, $domain->minAge());
        self::assertSame(35, $domain->maxAge());
        self::assertSame(10.5, $domain->distance()->kilometers());
        self::assertSame('M', $domain->gender());
    }

    public function testToDomainHandlesNullables(): void
    {
        $orm = new OrmCategory();
        $orm->setId('cat-2');
        $orm->setName('Open');
        $orm->setDistanceKm(5.0);

        $domain = $this->mapper->toDomain($orm);

        self::assertNull($domain->minAge());
        self::assertNull($domain->maxAge());
        self::assertNull($domain->gender());
    }

    public function testToOrmCreatesNewEntity(): void
    {
        $domain = new DomainCategory(
            id: 'cat-3',
            name: 'Veterano',
            minAge: 40,
            maxAge: 50,
            distance: Distance::fromKilometers(8.0),
            gender: 'F',
        );

        $orm = $this->mapper->toOrm($domain);

        self::assertInstanceOf(OrmCategory::class, $orm);
        self::assertSame('cat-3', $orm->getId());
        self::assertSame('Veterano', $orm->getName());
        self::assertSame(40, $orm->getMinAge());
        self::assertSame(50, $orm->getMaxAge());
        self::assertSame(8.0, $orm->getDistanceKm());
        self::assertSame('F', $orm->getGender());
    }

    public function testToOrmUpdatesExistingEntity(): void
    {
        $existing = new OrmCategory();
        $existing->setId('cat-4');
        $existing->setName('Old Name');
        $existing->setDistanceKm(3.0);

        $domain = new DomainCategory(
            id: 'cat-4',
            name: 'Updated Name',
            minAge: null,
            maxAge: null,
            distance: Distance::fromKilometers(12.0),
            gender: null,
        );

        $orm = $this->mapper->toOrm($domain, $existing);

        self::assertSame($existing, $orm);
        self::assertSame('Updated Name', $orm->getName());
        self::assertSame(12.0, $orm->getDistanceKm());
    }
}
