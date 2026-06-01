<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Race\Entity\RaceEdition as DomainRaceEdition;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Entity\Category as OrmCategory;
use App\Entity\RaceEdition as OrmRaceEdition;
use App\Infrastructure\Persistence\Doctrine\Mapper\CategoryMapper;
use App\Infrastructure\Persistence\Doctrine\Mapper\RaceEditionMapper;
use PHPUnit\Framework\TestCase;

final class RaceEditionMapperTest extends TestCase
{
    private RaceEditionMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new RaceEditionMapper(new CategoryMapper());
    }

    public function testToDomainMapsAllFields(): void
    {
        $orm = new OrmRaceEdition();
        $orm->setId('550e8400-e29b-41d4-a716-446655440500');
        $orm->setYear(2025);
        $orm->setName('X Carrera');
        $orm->setDescription('Test description');
        $orm->setDate(new \DateTimeImmutable('2025-07-01'));
        $orm->setLocation('Coca de Alba');
        $orm->setIsActive(true);
        $orm->setPosterUrl('race/2025/docs/cartel.webp');
        $orm->setRegistrationUrl('https://register.com');
        $orm->setShirtUrl('race/2025/docs/camiseta.webp');

        $domain = $this->mapper->toDomain($orm);

        self::assertSame('550e8400-e29b-41d4-a716-446655440500', $domain->id()->value());
        self::assertSame(2025, $domain->year()->value());
        self::assertSame('X Carrera', $domain->name());
        self::assertSame('Test description', $domain->description());
        self::assertSame('2025-07-01', $domain->date()->format('Y-m-d'));
        self::assertSame('Coca de Alba', $domain->location());
        self::assertTrue($domain->isActive());
        self::assertSame('race/2025/docs/cartel.webp', $domain->posterUrl());
        self::assertSame('https://register.com', $domain->registrationUrl());
        self::assertSame('race/2025/docs/camiseta.webp', $domain->shirtUrl());
    }

    public function testToDomainMapsCategories(): void
    {
        $orm = new OrmRaceEdition();
        $orm->setId('550e8400-e29b-41d4-a716-446655440501');
        $orm->setYear(2025);
        $orm->setName('X Carrera');
        $orm->setDescription('Test');
        $orm->setDate(new \DateTimeImmutable('2025-07-01'));
        $orm->setLocation('Coca de Alba');

        $category = new OrmCategory();
        $category->setId('cat-1');
        $category->setName('Senior');
        $category->setDistanceKm(10.0);
        $orm->addCategory($category);

        $domain = $this->mapper->toDomain($orm);

        self::assertCount(1, $domain->categories());
        self::assertSame('cat-1', $domain->categories()[0]->id());
    }

    public function testToOrmCreatesNewEntity(): void
    {
        $domain = new DomainRaceEdition(
            id: RaceEditionId::fromString('550e8400-e29b-41d4-a716-446655440502'),
            year: EditionYear::fromInt(2025),
            name: 'New Race',
            description: 'Desc',
            date: new \DateTimeImmutable('2025-08-15'),
            location: 'Salamanca',
            isActive: false,
            posterUrl: 'race/2025/docs/cartel.webp',
            registrationUrl: 'https://new.com',
            shirtUrl: 'race/2025/docs/camiseta.webp',
        );

        $orm = $this->mapper->toOrm($domain);

        self::assertInstanceOf(OrmRaceEdition::class, $orm);
        self::assertSame('550e8400-e29b-41d4-a716-446655440502', $orm->getId());
        self::assertSame(2025, $orm->getYear());
        self::assertSame('New Race', $orm->getName());
        self::assertSame('Salamanca', $orm->getLocation());
        self::assertFalse($orm->isActive());
    }

    public function testToOrmUpdatesExistingEntity(): void
    {
        $existing = new OrmRaceEdition();
        $existing->setId('550e8400-e29b-41d4-a716-446655440503');
        $existing->setYear(2024);
        $existing->setName('Old Name');
        $existing->setDescription('Old Desc');
        $existing->setDate(new \DateTimeImmutable('2024-07-01'));
        $existing->setLocation('Old Loc');

        $domain = new DomainRaceEdition(
            id: RaceEditionId::fromString('550e8400-e29b-41d4-a716-446655440503'),
            year: EditionYear::fromInt(2025),
            name: 'Updated Name',
            description: 'Updated Desc',
            date: new \DateTimeImmutable('2025-07-01'),
            location: 'Updated Loc',
        );

        $orm = $this->mapper->toOrm($domain, $existing);

        self::assertSame($existing, $orm);
        self::assertSame(2025, $orm->getYear());
        self::assertSame('Updated Name', $orm->getName());
        self::assertSame('Updated Loc', $orm->getLocation());
    }
}
