<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminSponsorControllerTest extends WebTestCase
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

    public function testListRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/admin/sponsors');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateAndListSponsor(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/admin/sponsors', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test Sponsor',
            'logoUrl' => 'https://example.com/logo.png',
            'website' => 'https://example.com',
            'tier' => 'gold',
            'isActive' => true,
            'sortOrder' => 1,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);

        $client->request('GET', '/api/v1/admin/sponsors');
        $this->assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $list);
        $this->assertIsArray($list['data']);

        $sponsorNames = array_column($list['data'], 'name');
        $this->assertContains('Test Sponsor', $sponsorNames);
    }

    public function testUpdateSponsor(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/admin/sponsors', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Original Name',
            'tier' => 'bronze',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/admin/sponsors');
        $list = json_decode($client->getResponse()->getContent(), true);
        $sponsorId = $list['data'][0]['id'];

        $client->request('PUT', '/api/v1/admin/sponsors/'.$sponsorId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Updated Name',
            'tier' => 'platinum',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['data']['updated']);
    }

    public function testDeleteSponsor(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/admin/sponsors', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'To Delete',
            'tier' => 'bronze',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/admin/sponsors');
        $list = json_decode($client->getResponse()->getContent(), true);
        $sponsorId = $list['data'][0]['id'];

        $client->request('DELETE', '/api/v1/admin/sponsors/'.$sponsorId);
        $this->assertResponseStatusCodeSame(204);
    }
}
