<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\RaceEdition;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RaceControllerTest extends WebTestCase
{
    private function seedEdition(EntityManagerInterface $em, int $year, string $name, bool $active): void
    {
        $edition = new RaceEdition();
        $edition->setId(Uuid::uuid4()->toString());
        $edition->setYear($year);
        $edition->setName($name);
        $edition->setDescription('Test description');
        $edition->setDate(new \DateTimeImmutable('2026-07-05'));
        $edition->setLocation('Coca de Alba');
        $edition->setIsActive($active);

        $em->persist($edition);
        $em->flush();
    }

    public function testListEditions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/editions');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testActiveEdition(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->seedEdition($em, 2026, 'IX Carrera Popular', true);
        $client->request('GET', '/api/v1/editions/active');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('year', $data['data']);
        $this->assertArrayHasKey('isActive', $data['data']);
        $this->assertTrue($data['data']['isActive']);
    }

    public function testShowEditionByYear(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->seedEdition($em, 2026, 'IX Carrera Popular', false);
        $client->request('GET', '/api/v1/editions/2026');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertSame(2026, $data['data']['year']);
    }

    public function testShowEditionInvalidYear(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/editions/1999');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testShowEditionNotFound(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Use a unique year at the upper bound to avoid collisions with other tests
        $year = 2028;
        $existing = $em->getRepository(RaceEdition::class)->findBy(['year' => $year]);
        foreach ($existing as $e) {
            $em->remove($e);
        }
        $em->flush();

        $client->request('GET', '/api/v1/editions/'.$year);

        $this->assertResponseStatusCodeSame(404);
    }
}
