<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminClubMemberControllerTest extends WebTestCase
{
    private function createAuthenticatedClient(string $role = 'ROLE_ADMIN'): array
    {
        $client = static::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'testclub_'.uniqid().'@cokalba.es';
        $userId = Uuid::uuid4()->toString();

        $user = new User();
        $user->setId($userId);
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('Club');
        $user->setRoles([$role]);
        $user->setPassword($hasher->hashPassword($user, 'testpass'));

        $em->persist($user);
        $em->flush();

        $client->request('POST', '/api/v1/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'password' => 'testpass']));

        $response = json_decode($client->getResponse()->getContent(), true);
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $response['token']);

        return [$client, $userId];
    }

    public function testListRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/admin/club-members');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateAndListMember(): void
    {
        [$client] = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/admin/club-members', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test Member',
            'description' => 'Presidente',
            'bio' => 'Bio de prueba',
            'isActive' => true,
            'sortOrder' => 1,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);

        $client->request('GET', '/api/v1/admin/club-members');
        $this->assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($list['data']);

        $names = array_column($list['data'], 'name');
        $this->assertContains('Test Member', $names);
    }

    public function testCreateMemberWithUserId(): void
    {
        [$client, $userId] = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/admin/club-members', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Assigned Member',
            'userId' => $userId,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $created = json_decode($client->getResponse()->getContent(), true);
        $createdId = $created['data']['id'];

        $client->request('GET', '/api/v1/admin/club-members');
        $list = json_decode($client->getResponse()->getContent(), true);
        $members = array_values(array_filter($list['data'], fn ($m) => $m['id'] === $createdId));
        $this->assertNotEmpty($members);
        $this->assertSame($userId, $members[0]['userId']);
    }

    public function testUpdateMember(): void
    {
        [$client, $userId] = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/admin/club-members', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Original Name',
            'bio' => 'Original bio',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/admin/club-members');
        $list = json_decode($client->getResponse()->getContent(), true);
        $memberId = $list['data'][0]['id'];

        $client->request('PUT', '/api/v1/admin/club-members/'.$memberId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Updated Name',
            'bio' => 'Updated bio',
            'userId' => $userId,
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['data']['updated']);

        $container = static::getContainer();
        $repo = $container->get(ClubMemberRepositoryInterface::class);
        $updated = $repo->findById($memberId);
        $this->assertSame('Updated Name', $updated->name());
        $this->assertSame($userId, $updated->userId());
    }

    public function testDeleteMember(): void
    {
        [$client] = $this->createAuthenticatedClient();

        $client->request('POST', '/api/v1/admin/club-members', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'To Delete',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/admin/club-members');
        $list = json_decode($client->getResponse()->getContent(), true);
        $memberId = $list['data'][0]['id'];

        $client->request('DELETE', '/api/v1/admin/club-members/'.$memberId);
        $this->assertResponseStatusCodeSame(204);
    }
}
