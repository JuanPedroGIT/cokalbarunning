<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Race\Entity\Category as DomainCategory;
use App\Domain\Race\ValueObject\Distance;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Registration\Entity\Runner as DomainRunner;
use App\Domain\Registration\ValueObject\BibNumber;
use App\Domain\Results\Entity\Result as DomainResult;
use App\Domain\Results\ValueObject\FinishTime;
use App\Domain\Results\ValueObject\Position;
use App\Entity\Category as OrmCategory;
use App\Entity\RaceEdition as OrmRaceEdition;
use App\Entity\Result as OrmResult;
use App\Entity\Runner as OrmRunner;
use App\Infrastructure\Persistence\Doctrine\Mapper\CategoryMapper;
use App\Infrastructure\Persistence\Doctrine\Mapper\ResultMapper;
use App\Infrastructure\Persistence\Doctrine\Mapper\RunnerMapper;
use PHPUnit\Framework\TestCase;

final class ResultMapperTest extends TestCase
{
    private ResultMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ResultMapper(new RunnerMapper(), new CategoryMapper());
    }

    public function testToDomainMapsAllFields(): void
    {
        $edition = new OrmRaceEdition();
        $edition->setId('550e8400-e29b-41d4-a716-446655440700');

        $runner = new OrmRunner();
        $runner->setId('550e8400-e29b-41d4-a716-446655440701');
        $runner->setFirstName('Juan');
        $runner->setLastName('Pérez');

        $category = new OrmCategory();
        $category->setId('550e8400-e29b-41d4-a716-446655440702');
        $category->setName('Senior');
        $category->setDistanceKm(10.0);

        $orm = new OrmResult();
        $orm->setId('550e8400-e29b-41d4-a716-446655440703');
        $orm->setRaceEdition($edition);
        $orm->setRunner($runner);
        $orm->setBibNumber('123');
        $orm->setCategory($category);
        $orm->setFinishTimeSeconds(3661);
        $orm->setPosition(5);
        $orm->setGenderPosition(3);
        $orm->setCategoryPosition(2);

        $domain = $this->mapper->toDomain($orm);

        self::assertSame('550e8400-e29b-41d4-a716-446655440703', $domain->id());
        self::assertSame('550e8400-e29b-41d4-a716-446655440700', $domain->raceEditionId()->value());
        self::assertSame('Juan', $domain->runner()->firstName());
        self::assertSame('123', $domain->bibNumber()->value());
        self::assertSame('Senior', $domain->category()->name());
        self::assertSame(3661, $domain->finishTime()->seconds());
        self::assertSame(5, $domain->position()?->value());
        self::assertSame(3, $domain->genderPosition()?->value());
        self::assertSame(2, $domain->categoryPosition()?->value());
    }

    public function testToDomainHandlesNullPositions(): void
    {
        $edition = new OrmRaceEdition();
        $edition->setId('550e8400-e29b-41d4-a716-446655440704');

        $runner = new OrmRunner();
        $runner->setId('550e8400-e29b-41d4-a716-446655440705');
        $runner->setFirstName('Ana');
        $runner->setLastName('García');

        $category = new OrmCategory();
        $category->setId('550e8400-e29b-41d4-a716-446655440706');
        $category->setName('Veterana');
        $category->setDistanceKm(5.0);

        $orm = new OrmResult();
        $orm->setId('550e8400-e29b-41d4-a716-446655440707');
        $orm->setRaceEdition($edition);
        $orm->setRunner($runner);
        $orm->setBibNumber('456');
        $orm->setCategory($category);
        $orm->setFinishTimeSeconds(1800);

        $domain = $this->mapper->toDomain($orm);

        self::assertNull($domain->position());
        self::assertNull($domain->genderPosition());
        self::assertNull($domain->categoryPosition());
    }

    public function testToOrmCreatesNewEntity(): void
    {
        $domain = new DomainResult(
            id: '550e8400-e29b-41d4-a716-446655440708',
            raceEditionId: RaceEditionId::fromString('550e8400-e29b-41d4-a716-446655440709'),
            runner: new DomainRunner(id: '550e8400-e29b-41d4-a716-446655440710', firstName: 'Test', lastName: 'Runner'),
            bibNumber: BibNumber::fromString('789'),
            category: new DomainCategory(id: '550e8400-e29b-41d4-a716-446655440711', name: 'Open', minAge: null, maxAge: null, distance: Distance::fromKilometers(10.0)),
            finishTime: FinishTime::fromSeconds(3600),
            position: Position::fromInt(1),
        );

        $orm = $this->mapper->toOrm($domain);

        self::assertInstanceOf(OrmResult::class, $orm);
        self::assertSame('789', $orm->getBibNumber());
        self::assertSame(3600, $orm->getFinishTimeSeconds());
        self::assertSame(1, $orm->getPosition());
        self::assertNull($orm->getGenderPosition());
    }
}
