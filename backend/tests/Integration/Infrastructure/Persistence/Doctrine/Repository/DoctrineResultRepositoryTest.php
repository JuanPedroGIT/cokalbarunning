<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Race\Entity\Category as DomainCategory;
use App\Domain\Race\ValueObject\Distance;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Domain\Registration\Entity\Runner;
use App\Domain\Registration\ValueObject\BibNumber;
use App\Domain\Results\Entity\Result;
use App\Domain\Results\ValueObject\FinishTime;
use App\Domain\Results\ValueObject\Position;
use App\Entity\Category as OrmCategory;
use App\Entity\RaceEdition as OrmRaceEdition;
use App\Entity\Runner as OrmRunner;
use App\Infrastructure\Persistence\Doctrine\Repository\DoctrineResultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineResultRepositoryTest extends KernelTestCase
{
    private DoctrineResultRepository $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(DoctrineResultRepository::class);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function seedEdition(string $id, int $year): OrmRaceEdition
    {
        $edition = new OrmRaceEdition();
        $edition->setId($id);
        $edition->setYear($year);
        $edition->setName('Test Edition '.$year);
        $edition->setDescription('Test');
        $edition->setDate(new \DateTimeImmutable('2025-07-01'));
        $edition->setLocation('Coca de Alba');
        $this->em->persist($edition);
        $this->em->flush();

        return $edition;
    }

    private function seedRunner(string $id, string $firstName, string $lastName): OrmRunner
    {
        $runner = new OrmRunner();
        $runner->setId($id);
        $runner->setFirstName($firstName);
        $runner->setLastName($lastName);
        $this->em->persist($runner);
        $this->em->flush();

        return $runner;
    }

    private function seedCategory(string $id, string $name, OrmRaceEdition $edition): OrmCategory
    {
        $category = new OrmCategory();
        $category->setId($id);
        $category->setName($name);
        $category->setDistanceKm(10.0);
        $category->setRaceEdition($edition);
        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    public function testSaveAndFindById(): void
    {
        $editionId = Uuid::uuid4()->toString();
        $runnerId = Uuid::uuid4()->toString();
        $categoryId = Uuid::uuid4()->toString();

        $this->seedEdition($editionId, 2025);
        $this->seedRunner($runnerId, 'Juan', 'Pérez');
        $this->seedCategory($categoryId, 'Senior', $this->em->getReference(OrmRaceEdition::class, $editionId));

        $result = new Result(
            id: 'result-test-1',
            raceEditionId: RaceEditionId::fromString($editionId),
            runner: new Runner(id: $runnerId, firstName: 'Juan', lastName: 'Pérez'),
            bibNumber: BibNumber::fromString('101'),
            category: new DomainCategory(id: $categoryId, name: 'Senior', minAge: null, maxAge: null, distance: Distance::fromKilometers(10.0)),
            finishTime: FinishTime::fromSeconds(3600),
            position: Position::fromInt(1),
        );

        $this->repository->save($result);

        $found = $this->repository->findById('result-test-1');

        self::assertNotNull($found);
        self::assertSame('101', $found->bibNumber()->value());
        self::assertSame(3600, $found->finishTime()->seconds());
        self::assertSame(1, $found->position()?->value());
    }

    public function testFindByRaceEdition(): void
    {
        $editionId = Uuid::uuid4()->toString();
        $runnerId = Uuid::uuid4()->toString();
        $categoryId = Uuid::uuid4()->toString();

        $this->seedEdition($editionId, 2024);
        $this->seedRunner($runnerId, 'Ana', 'García');
        $this->seedCategory($categoryId, 'Veterana', $this->em->getReference(OrmRaceEdition::class, $editionId));

        $result = new Result(
            id: 'result-test-2',
            raceEditionId: RaceEditionId::fromString($editionId),
            runner: new Runner(id: $runnerId, firstName: 'Ana', lastName: 'García'),
            bibNumber: BibNumber::fromString('202'),
            category: new DomainCategory(id: $categoryId, name: 'Veterana', minAge: null, maxAge: null, distance: Distance::fromKilometers(5.0)),
            finishTime: FinishTime::fromSeconds(1800),
        );

        $this->repository->save($result);

        $results = $this->repository->findByRaceEdition(RaceEditionId::fromString($editionId));
        self::assertCount(1, $results);
        self::assertSame('202', $results[0]->bibNumber()->value());
    }

    public function testClearPositionsForEdition(): void
    {
        $editionId = Uuid::uuid4()->toString();
        $runnerId = Uuid::uuid4()->toString();
        $categoryId = Uuid::uuid4()->toString();

        $this->seedEdition($editionId, 2023);
        $this->seedRunner($runnerId, 'Luis', 'Martínez');
        $this->seedCategory($categoryId, 'Open', $this->em->getReference(OrmRaceEdition::class, $editionId));

        $result = new Result(
            id: 'result-test-3',
            raceEditionId: RaceEditionId::fromString($editionId),
            runner: new Runner(id: $runnerId, firstName: 'Luis', lastName: 'Martínez'),
            bibNumber: BibNumber::fromString('303'),
            category: new DomainCategory(id: $categoryId, name: 'Open', minAge: null, maxAge: null, distance: Distance::fromKilometers(10.0)),
            finishTime: FinishTime::fromSeconds(2400),
            position: Position::fromInt(5),
            genderPosition: Position::fromInt(3),
            categoryPosition: Position::fromInt(2),
        );

        $this->repository->save($result);

        $found = $this->repository->findById('result-test-3');
        self::assertSame(5, $found->position()?->value());

        $this->repository->clearPositionsForEdition(RaceEditionId::fromString($editionId));
        $this->em->clear();

        $cleared = $this->repository->findById('result-test-3');
        self::assertNull($cleared->position());
        self::assertNull($cleared->genderPosition());
        self::assertNull($cleared->categoryPosition());
    }
}
