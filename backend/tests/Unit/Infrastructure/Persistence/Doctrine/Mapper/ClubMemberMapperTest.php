<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Club\Entity\ClubMember as DomainMember;
use App\Entity\ClubMember as OrmMember;
use App\Infrastructure\Persistence\Doctrine\Mapper\ClubMemberMapper;
use PHPUnit\Framework\TestCase;

final class ClubMemberMapperTest extends TestCase
{
    private ClubMemberMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ClubMemberMapper();
    }

    public function testToDomainMapsAllFields(): void
    {
        $orm = new OrmMember();
        $orm->setId('550e8400-e29b-41d4-a716-446655440000');
        $orm->setName('John Doe');
        $orm->setDescription('Presidente');
        $orm->setBio('Corredor desde 2015');
        $orm->setPhotoPath('members/john.png');
        $orm->setIsActive(true);
        $orm->setSortOrder(3);
        $orm->setUserId('user-uuid-123');

        $domain = $this->mapper->toDomain($orm);

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $domain->id());
        self::assertSame('John Doe', $domain->name());
        self::assertSame('Presidente', $domain->description());
        self::assertSame('Corredor desde 2015', $domain->bio());
        self::assertSame('members/john.png', $domain->photoPath());
        self::assertTrue($domain->isActive());
        self::assertSame(3, $domain->sortOrder());
        self::assertSame('user-uuid-123', $domain->userId());
    }

    public function testToDomainHandlesNullables(): void
    {
        $orm = new OrmMember();
        $orm->setId('550e8400-e29b-41d4-a716-446655440001');
        $orm->setName('Minimal Member');

        $domain = $this->mapper->toDomain($orm);

        self::assertNull($domain->description());
        self::assertNull($domain->bio());
        self::assertNull($domain->photoPath());
        self::assertTrue($domain->isActive());
        self::assertSame(0, $domain->sortOrder());
        self::assertNull($domain->userId());
    }

    public function testToOrmCreatesNewEntityWhenNoOrmProvided(): void
    {
        $domain = new DomainMember(
            id: '550e8400-e29b-41d4-a716-446655440002',
            name: 'New Member',
            description: 'Vicepresidente',
            bio: 'Apasionado del trail',
            photoPath: 'members/new.png',
            isActive: false,
            sortOrder: 7,
            userId: 'user-uuid-456',
        );

        $orm = $this->mapper->toOrm($domain);

        self::assertInstanceOf(OrmMember::class, $orm);
        self::assertSame('550e8400-e29b-41d4-a716-446655440002', $orm->getId());
        self::assertSame('New Member', $orm->getName());
        self::assertSame('Vicepresidente', $orm->getDescription());
        self::assertSame('Apasionado del trail', $orm->getBio());
        self::assertSame('members/new.png', $orm->getPhotoPath());
        self::assertFalse($orm->isActive());
        self::assertSame(7, $orm->getSortOrder());
        self::assertSame('user-uuid-456', $orm->getUserId());
    }

    public function testToOrmUpdatesExistingEntity(): void
    {
        $existing = new OrmMember();
        $existing->setId('550e8400-e29b-41d4-a716-446655440003');
        $existing->setName('Old Name');
        $existing->setUserId('old-user');

        $domain = new DomainMember(
            id: '550e8400-e29b-41d4-a716-446655440003',
            name: 'Updated Name',
            userId: 'new-user',
        );

        $orm = $this->mapper->toOrm($domain, $existing);

        self::assertSame($existing, $orm);
        self::assertSame('Updated Name', $orm->getName());
        self::assertSame('new-user', $orm->getUserId());
    }

    public function testToOrmPreservesExistingUserIdWhenDomainHasNone(): void
    {
        $existing = new OrmMember();
        $existing->setId('550e8400-e29b-41d4-a716-446655440004');
        $existing->setName('Existing');
        $existing->setUserId('preserved-user');

        $domain = new DomainMember(
            id: '550e8400-e29b-41d4-a716-446655440004',
            name: 'Updated Name',
        );

        $orm = $this->mapper->toOrm($domain, $existing);

        self::assertSame('Updated Name', $orm->getName());
        self::assertSame('preserved-user', $orm->getUserId());
    }
}
