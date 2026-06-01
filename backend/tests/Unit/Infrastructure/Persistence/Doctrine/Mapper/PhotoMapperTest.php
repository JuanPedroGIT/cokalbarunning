<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Media\Entity\Photo as DomainPhoto;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Entity\Photo as OrmPhoto;
use App\Entity\RaceEdition as OrmRaceEdition;
use App\Infrastructure\Persistence\Doctrine\Mapper\PhotoMapper;
use PHPUnit\Framework\TestCase;

final class PhotoMapperTest extends TestCase
{
    private PhotoMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new PhotoMapper();
    }

    public function testToDomainMapsAllFields(): void
    {
        $raceEdition = new OrmRaceEdition();
        $raceEdition->setId('550e8400-e29b-41d4-a716-446655440300');

        $orm = new OrmPhoto();
        $orm->setId('550e8400-e29b-41d4-a716-446655440301');
        $orm->setOriginalPath('photos/photo.jpg');
        $orm->setThumbPath('photos/thumbs/photo.webp');
        $orm->setRaceEdition($raceEdition);
        $orm->setAltText('Test alt text');
        $orm->setIsFeatured(true);
        $orm->setSortOrder(5);

        $domain = $this->mapper->toDomain($orm);

        self::assertSame('550e8400-e29b-41d4-a716-446655440301', $domain->id());
        self::assertSame('photos/photo.jpg', $domain->originalPath());
        self::assertSame('photos/thumbs/photo.webp', $domain->thumbPath());
        self::assertNotNull($domain->raceEditionId());
        self::assertSame('550e8400-e29b-41d4-a716-446655440300', $domain->raceEditionId()->value());
        self::assertSame('Test alt text', $domain->altText());
        self::assertTrue($domain->isFeatured());
        self::assertSame(5, $domain->sortOrder());
    }

    public function testToDomainHandlesNullables(): void
    {
        $orm = new OrmPhoto();
        $orm->setId('550e8400-e29b-41d4-a716-446655440302');
        $orm->setOriginalPath('photo.jpg');

        $domain = $this->mapper->toDomain($orm);

        self::assertNull($domain->thumbPath());
        self::assertNull($domain->raceEditionId());
        self::assertNull($domain->altText());
        self::assertFalse($domain->isFeatured());
        self::assertSame(0, $domain->sortOrder());
    }

    public function testToOrmCreatesNewEntity(): void
    {
        $domain = new DomainPhoto(
            id: '550e8400-e29b-41d4-a716-446655440303',
            originalPath: 'photos/new.jpg',
            thumbPath: 'photos/thumbs/new.webp',
            raceEditionId: RaceEditionId::fromString('550e8400-e29b-41d4-a716-446655440300'),
            altText: 'New alt',
            isFeatured: true,
            sortOrder: 10,
        );

        $orm = $this->mapper->toOrm($domain);

        self::assertInstanceOf(OrmPhoto::class, $orm);
        self::assertSame('550e8400-e29b-41d4-a716-446655440303', $orm->getId());
        self::assertSame('photos/new.jpg', $orm->getOriginalPath());
        self::assertSame('photos/thumbs/new.webp', $orm->getThumbPath());
        self::assertSame('New alt', $orm->getAltText());
        self::assertTrue($orm->isFeatured());
        self::assertSame(10, $orm->getSortOrder());
    }

    public function testToOrmUpdatesExistingEntity(): void
    {
        $existing = new OrmPhoto();
        $existing->setId('550e8400-e29b-41d4-a716-446655440304');
        $existing->setOriginalPath('old.jpg');
        $existing->setAltText('Old alt');

        $domain = new DomainPhoto(
            id: '550e8400-e29b-41d4-a716-446655440304',
            originalPath: 'updated.jpg',
            altText: 'Updated alt',
            isFeatured: true,
        );

        $orm = $this->mapper->toOrm($domain, $existing);

        self::assertSame($existing, $orm);
        self::assertSame('updated.jpg', $orm->getOriginalPath());
        self::assertSame('Updated alt', $orm->getAltText());
        self::assertTrue($orm->isFeatured());
    }
}
