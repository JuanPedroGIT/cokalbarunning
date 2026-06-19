<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\RaceEdition;
use App\Entity\Runner;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RunnerControllerTest extends WebTestCase
{
    private function seedEdition(EntityManagerInterface $em): RaceEdition
    {
        $edition = new RaceEdition();
        $edition->setId(Uuid::uuid4()->toString());
        $edition->setYear(2026);
        $edition->setName('IX Carrera Popular');
        $edition->setDescription('Test description');
        $edition->setDate(new \DateTimeImmutable('2026-07-05'));
        $edition->setLocation('Coca de Alba');
        $edition->setIsActive(true);

        $em->persist($edition);
        $em->flush();

        return $edition;
    }

    private function seedRunner(EntityManagerInterface $em, string $editionId, string $firstName, string $lastName, string $email, string $bibNumber): void
    {
        $runner = new Runner();
        $runner->setId(Uuid::uuid4()->toString());
        $runner->setFirstName($firstName);
        $runner->setLastName($lastName);
        $runner->setEmail($email);
        $runner->setRaceEditionId($editionId);
        $runner->setBibNumber($bibNumber);

        $em->persist($runner);
        $em->flush();
    }

    public function testSearchRequiresEditionId(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/runners?name=Juan');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSearchRequiresNameWithMinimumLength(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $edition = $this->seedEdition($em);

        $client->request('GET', sprintf('/api/v1/runners?editionId=%s&name=Ju', $edition->getId()));

        $this->assertResponseStatusCodeSame(400);

        $client->request('GET', sprintf('/api/v1/runners?editionId=%s&name=Juan', $edition->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testSearchRunnersByName(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $edition = $this->seedEdition($em);

        $this->seedRunner($em, $edition->getId(), 'Juan', 'Pérez', 'juan@example.com', '001');
        $this->seedRunner($em, $edition->getId(), 'Ana', 'García', 'ana@example.com', '002');

        $client->request('GET', sprintf('/api/v1/runners?editionId=%s&name=juan', $edition->getId()));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $data['data']);
        $this->assertSame('Juan Pérez', $data['data'][0]['fullName']);
        $this->assertSame('001', $data['data'][0]['bibNumber']);
    }

    public function testSearchRunnersByLastName(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $edition = $this->seedEdition($em);

        $this->seedRunner($em, $edition->getId(), 'Juan', 'Pérez López', 'juan@example.com', '001');

        $client->request('GET', sprintf('/api/v1/runners?editionId=%s&name=lópez', $edition->getId()));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $data['data']);
        $this->assertSame('Juan Pérez López', $data['data'][0]['fullName']);
    }

    public function testSearchFiltersByEdition(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $edition2026 = $this->seedEdition($em);

        $edition2025 = new RaceEdition();
        $edition2025->setId(Uuid::uuid4()->toString());
        $edition2025->setYear(2025);
        $edition2025->setName('VIII Carrera Popular');
        $edition2025->setDescription('Test description');
        $edition2025->setDate(new \DateTimeImmutable('2025-07-05'));
        $edition2025->setLocation('Coca de Alba');
        $edition2025->setIsActive(false);
        $em->persist($edition2025);
        $em->flush();

        $this->seedRunner($em, $edition2026->getId(), 'Juan', 'Pérez', 'juan@example.com', '001');
        $this->seedRunner($em, $edition2025->getId(), 'Juan', 'Pérez', 'juan2@example.com', '101');

        $client->request('GET', sprintf('/api/v1/runners?editionId=%s&name=juan', $edition2026->getId()));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $data['data']);
        $this->assertSame('001', $data['data'][0]['bibNumber']);
    }
}
