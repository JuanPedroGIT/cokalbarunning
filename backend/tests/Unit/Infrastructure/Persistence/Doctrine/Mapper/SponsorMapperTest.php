<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Club\Entity\Sponsor as DomainSponsor;
use App\Entity\Sponsor as OrmSponsor;
use App\Infrastructure\Persistence\Doctrine\Mapper\SponsorMapper;
use PHPUnit\Framework\TestCase;

final class SponsorMapperTest extends TestCase
{
    private SponsorMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new SponsorMapper();
    }

    public function testToDomainMapsAllFields(): void
    {
        $orm = new OrmSponsor();
        $orm->setId('550e8400-e29b-41d4-a716-446655440000');
        $orm->setName('Acme Corp');
        $orm->setLogoUrl('sponsors/logo.png');
        $orm->setWebsite('https://acme.com');
        $orm->setTier('gold');
        $orm->setIsActive(true);
        $orm->setSortOrder(5);

        $domain = $this->mapper->toDomain($orm);

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $domain->id());
        self::assertSame('Acme Corp', $domain->name());
        self::assertSame('sponsors/logo.png', $domain->logoUrl());
        self::assertSame('https://acme.com', $domain->website());
        self::assertSame('gold', $domain->tier());
        self::assertTrue($domain->isActive());
        self::assertSame(5, $domain->sortOrder());
        self::assertNull($domain->message());
    }

    public function testToDomainHandlesNullables(): void
    {
        $orm = new OrmSponsor();
        $orm->setId('550e8400-e29b-41d4-a716-446655440001');
        $orm->setName('Minimal Sponsor');

        $domain = $this->mapper->toDomain($orm);

        self::assertNull($domain->logoUrl());
        self::assertNull($domain->website());
        self::assertSame('bronze', $domain->tier());
        self::assertTrue($domain->isActive());
        self::assertSame(0, $domain->sortOrder());
        self::assertNull($domain->message());
    }

    public function testToOrmCreatesNewEntityWhenNoOrmProvided(): void
    {
        $domain = new DomainSponsor(
            id: '550e8400-e29b-41d4-a716-446655440002',
            name: 'New Sponsor',
            logoUrl: 'sponsors/new.png',
            website: 'https://new.com',
            tier: 'silver',
            isActive: false,
            sortOrder: 10,
            message: 'Test message',
        );

        $orm = $this->mapper->toOrm($domain);

        self::assertInstanceOf(OrmSponsor::class, $orm);
        self::assertSame('550e8400-e29b-41d4-a716-446655440002', $orm->getId());
        self::assertSame('New Sponsor', $orm->getName());
        self::assertSame('sponsors/new.png', $orm->getLogoUrl());
        self::assertSame('https://new.com', $orm->getWebsite());
        self::assertSame('silver', $orm->getTier());
        self::assertFalse($orm->isActive());
        self::assertSame(10, $orm->getSortOrder());
    }

    public function testToOrmUpdatesExistingEntity(): void
    {
        $existing = new OrmSponsor();
        $existing->setId('550e8400-e29b-41d4-a716-446655440003');
        $existing->setName('Old Name');
        $existing->setTier('bronze');

        $domain = new DomainSponsor(
            id: '550e8400-e29b-41d4-a716-446655440003',
            name: 'Updated Name',
            tier: 'platinum',
        );

        $orm = $this->mapper->toOrm($domain, $existing);

        self::assertSame($existing, $orm);
        self::assertSame('Updated Name', $orm->getName());
        self::assertSame('platinum', $orm->getTier());
    }
}
