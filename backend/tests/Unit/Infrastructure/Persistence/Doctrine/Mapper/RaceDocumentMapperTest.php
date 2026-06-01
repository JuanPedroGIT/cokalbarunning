<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Race\Entity\RaceDocument as DomainRaceDocument;
use App\Entity\RaceDocument as OrmRaceDocument;
use App\Infrastructure\Persistence\Doctrine\Mapper\RaceDocumentMapper;
use PHPUnit\Framework\TestCase;

final class RaceDocumentMapperTest extends TestCase
{
    private RaceDocumentMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new RaceDocumentMapper();
    }

    public function testToDomainMapsAllFields(): void
    {
        $orm = new OrmRaceDocument();
        $orm->setId('550e8400-e29b-41d4-a716-446655440600');
        $orm->setName('Recorrido Niños');
        $orm->setType('route');
        $orm->setFilePath('race/2026/docs/recorrido_ninos.pdf');
        $orm->setCreatedAt(new \DateTimeImmutable('2026-05-01 10:00:00'));

        $domain = $this->mapper->toDomain($orm);

        self::assertSame('550e8400-e29b-41d4-a716-446655440600', $domain->id());
        self::assertSame('Recorrido Niños', $domain->name());
        self::assertSame('route', $domain->type()->value());
        self::assertSame('race/2026/docs/recorrido_ninos.pdf', $domain->filePath());
        self::assertNull($domain->editionId());
    }

    public function testToOrmCreatesNewEntity(): void
    {
        $domain = DomainRaceDocument::create(
            id: '550e8400-e29b-41d4-a716-446655440601',
            name: 'Clasificación General',
            type: 'results',
            filePath: 'race/2026/results/clasificacion.pdf',
        );

        $orm = $this->mapper->toOrm($domain);

        self::assertInstanceOf(OrmRaceDocument::class, $orm);
        self::assertSame('550e8400-e29b-41d4-a716-446655440601', $orm->getId());
        self::assertSame('Clasificación General', $orm->getName());
        self::assertSame('results', $orm->getType());
        self::assertSame('race/2026/results/clasificacion.pdf', $orm->getFilePath());
    }
}
