<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Registration\Entity\Runner as DomainRunner;
use App\Entity\Runner as OrmRunner;
use App\Infrastructure\Persistence\Doctrine\Mapper\RunnerMapper;
use PHPUnit\Framework\TestCase;

final class RunnerMapperTest extends TestCase
{
    private RunnerMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new RunnerMapper();
    }

    public function testToDomainMapsAllFields(): void
    {
        $orm = new OrmRunner();
        $orm->setId('550e8400-e29b-41d4-a716-446655440600');
        $orm->setFirstName('Juan');
        $orm->setLastName('Pérez');
        $orm->setEmail('juan@example.com');
        $orm->setClub('Coca Running');
        $orm->setBirthDate(new \DateTimeImmutable('1990-05-15'));
        $orm->setGender('M');

        $domain = $this->mapper->toDomain($orm);

        self::assertSame('550e8400-e29b-41d4-a716-446655440600', $domain->id());
        self::assertSame('Juan', $domain->firstName());
        self::assertSame('Pérez', $domain->lastName());
        self::assertSame('juan@example.com', $domain->email());
        self::assertSame('Coca Running', $domain->club());
        self::assertSame('1990-05-15', $domain->birthDate()?->format('Y-m-d'));
        self::assertSame('M', $domain->gender());
        self::assertSame('Juan Pérez', $domain->fullName());
    }

    public function testToDomainHandlesNullables(): void
    {
        $orm = new OrmRunner();
        $orm->setId('550e8400-e29b-41d4-a716-446655440601');
        $orm->setFirstName('Ana');
        $orm->setLastName('García');

        $domain = $this->mapper->toDomain($orm);

        self::assertNull($domain->email());
        self::assertNull($domain->club());
        self::assertNull($domain->birthDate());
        self::assertNull($domain->gender());
    }

    public function testToOrmCreatesNewEntity(): void
    {
        $domain = new DomainRunner(
            id: '550e8400-e29b-41d4-a716-446655440602',
            firstName: 'Luis',
            lastName: 'Martínez',
            email: 'luis@example.com',
            club: 'Runners SA',
            birthDate: new \DateTimeImmutable('1985-03-20'),
            gender: 'M',
        );

        $orm = $this->mapper->toOrm($domain);

        self::assertInstanceOf(OrmRunner::class, $orm);
        self::assertSame('Luis', $orm->getFirstName());
        self::assertSame('Martínez', $orm->getLastName());
        self::assertSame('luis@example.com', $orm->getEmail());
        self::assertSame('Runners SA', $orm->getClub());
        self::assertSame('1985-03-20', $orm->getBirthDate()?->format('Y-m-d'));
        self::assertSame('M', $orm->getGender());
    }

    public function testToOrmUpdatesExistingEntity(): void
    {
        $existing = new OrmRunner();
        $existing->setId('550e8400-e29b-41d4-a716-446655440603');
        $existing->setFirstName('Old');
        $existing->setLastName('Name');

        $domain = new DomainRunner(
            id: '550e8400-e29b-41d4-a716-446655440603',
            firstName: 'Updated',
            lastName: 'Runner',
        );

        $orm = $this->mapper->toOrm($domain, $existing);

        self::assertSame($existing, $orm);
        self::assertSame('Updated', $orm->getFirstName());
        self::assertSame('Runner', $orm->getLastName());
    }
}
