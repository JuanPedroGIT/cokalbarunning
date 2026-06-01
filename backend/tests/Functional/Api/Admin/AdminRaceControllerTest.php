<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

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
        ], json_encode(['year' => 2028, 'name' => 'Test']));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateEdition(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/admin/editions', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'year' => 2028,
            'name' => 'XII Carrera Test',
            'description' => 'Descripcion de prueba',
            'date' => '2028-07-05',
            'location' => 'Coca de Alba',
            'isActive' => true,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['data']['created']);
        $this->assertArrayHasKey('id', $data['data']);
    }

    public function testUpdateEdition(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/admin/editions', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'year' => 2026,
            'name' => 'Original Name',
            'description' => 'Original Desc',
            'date' => '2026-07-05',
            'location' => 'Coca de Alba',
            'isActive' => true,
        ]));
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        $client->request('PUT', '/api/v1/admin/editions/'.$id, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Updated Name',
            'location' => 'Updated Location',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['data']['updated']);
    }

    public function testDeleteEdition(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/admin/editions', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'year' => 2025,
            'name' => 'To Delete',
            'description' => 'Desc',
            'date' => '2025-07-05',
            'location' => 'Coca de Alba',
        ]));
        $this->assertResponseStatusCodeSame(201);
        $id = json_decode($client->getResponse()->getContent(), true)['data']['id'];

        $client->request('DELETE', '/api/v1/admin/editions/'.$id);
        $this->assertResponseStatusCodeSame(204);
    }
}
