<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminRaceControllerTest extends WebTestCase
{
    private function createAuthenticatedClient(): mixed
    {
        $client = static::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'testadmin_'.uniqid().'@cokalba.es';

        $user = new User();
        $user->setId(Uuid::uuid4()->toString());
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('Admin');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($hasher->hashPassword($user, 'testpass'));

        $em->persist($user);
        $em->flush();

        $client->request('POST', '/api/v1/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => 'testpass']));

        $response = json_decode($client->getResponse()->getContent(), true);
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $response['token']);

        return $client;
    }

    public function testCreateEditionRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/admin/editions', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['year' => 2027, 'name' => 'Test']));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateEdition(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/admin/editions', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'year' => 2027,
            'name' => 'X Carrera Test',
            'description' => 'Descripcion de prueba',
            'date' => '2027-07-05',
            'location' => 'Coca de Alba',
            'isActive' => true,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['data']['created']);
        $this->assertArrayHasKey('id', $data['data']);
    }

    public function testCreateEditionWithShowBibSearch(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/admin/editions', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'year' => 2028,
            'name' => 'XI Carrera Test',
            'description' => 'Descripcion de prueba',
            'date' => '2028-07-05',
            'location' => 'Coca de Alba',
            'isActive' => true,
            'showBibSearch' => true,
        ]));

        $this->assertResponseStatusCodeSame(201);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $edition = $em->getRepository(\App\Entity\RaceEdition::class)->findOneBy(['year' => 2028]);
        $this->assertNotNull($edition);
        $this->assertTrue($edition->isShowBibSearch());
    }

    public function testUpdateEditionShowBibSearch(): void
    {
        $client = $this->createAuthenticatedClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $edition = new \App\Entity\RaceEdition();
        $edition->setId(Uuid::uuid4()->toString());
        $edition->setYear(2028);
        $edition->setName('XII Carrera Test');
        $edition->setDescription('Descripcion');
        $edition->setDate(new \DateTimeImmutable('2028-07-05'));
        $edition->setLocation('Coca de Alba');
        $edition->setIsActive(true);
        $edition->setShowBibSearch(false);
        $em->persist($edition);
        $em->flush();

        $client->request('PUT', '/api/v1/admin/editions/' . $edition->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'showBibSearch' => true,
        ]));

        $this->assertResponseIsSuccessful();

        $em->clear();
        $updated = $em->getRepository(\App\Entity\RaceEdition::class)->find($edition->getId());
        $this->assertTrue($updated->isShowBibSearch());
    }
}
