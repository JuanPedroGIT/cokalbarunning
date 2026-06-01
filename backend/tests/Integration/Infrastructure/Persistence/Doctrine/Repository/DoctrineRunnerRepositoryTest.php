<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Registration\Entity\Runner;
use App\Domain\Registration\Repository\RunnerRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineRunnerRepositoryTest extends KernelTestCase
{
    private RunnerRepositoryInterface $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(RunnerRepositoryInterface::class);
    }

    public function testSaveAndFindById(): void
    {
        $id = Uuid::uuid4()->toString();
        $runner = new Runner(
            id: $id,
            firstName: 'Juan',
            lastName: 'Pérez',
            email: 'juan@test.com',
            club: 'Coca Running',
            birthDate: new \DateTimeImmutable('1990-05-15'),
            gender: 'M',
        );

        $this->repository->save($runner);

        $found = $this->repository->findById($id);

        self::assertNotNull($found);
        self::assertSame('Juan', $found->firstName());
        self::assertSame('Pérez', $found->lastName());
        self::assertSame('juan@test.com', $found->email());
    }

    public function testFindByIdReturnsNullForMissing(): void
    {
        $found = $this->repository->findById(Uuid::uuid4()->toString());
        self::assertNull($found);
    }

    public function testFindByEmail(): void
    {
        $id = Uuid::uuid4()->toString();
        $runner = new Runner(
            id: $id,
            firstName: 'Ana',
            lastName: 'García',
            email: 'ana@unique.com',
        );

        $this->repository->save($runner);

        $found = $this->repository->findByEmail('ana@unique.com');
        self::assertNotNull($found);
        self::assertSame('Ana', $found->firstName());
    }

    public function testFindByEmailReturnsNullForMissing(): void
    {
        $found = $this->repository->findByEmail('missing@example.com');
        self::assertNull($found);
    }
}
