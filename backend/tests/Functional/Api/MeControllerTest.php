<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Club\Entity\ClubMember;
use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class MeControllerTest extends WebTestCase
{
    private function createAuthenticatedClient(string $role = 'ROLE_ADMIN'): array
    {
        $client = static::createClient();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'testme_'.uniqid().'@cokalba.es';
        $userId = Uuid::uuid4()->toString();

        $user = new User();
        $user->setId($userId);
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('Me');
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

    public function testClubProfileReturns404WhenNoMemberAssigned(): void
    {
        [$client] = $this->createAuthenticatedClient();

        $client->request('GET', '/api/v1/me/club-profile');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testClubProfileReturnsMemberWhenAssigned(): void
    {
        [$client, $userId] = $this->createAuthenticatedClient();
        $container = static::getContainer();

        $repo = $container->get(ClubMemberRepositoryInterface::class);
        $member = new ClubMember(
            id: Uuid::uuid4()->toString(),
            name: 'Test Runner',
            description: 'Corredor',
            bio: 'Me encanta correr',
            photoPath: null,
            isActive: true,
            sortOrder: 1,
            userId: $userId,
        );
        $repo->save($member);

        $client->request('GET', '/api/v1/me/club-profile');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Test Runner', $data['data']['name']);
        $this->assertSame('Me encanta correr', $data['data']['bio']);
        $this->assertSame($userId, $data['data']['userId']);
    }

    public function testUpdateClubProfile(): void
    {
        [$client, $userId] = $this->createAuthenticatedClient();
        $container = static::getContainer();

        $repo = $container->get(ClubMemberRepositoryInterface::class);
        $member = new ClubMember(
            id: Uuid::uuid4()->toString(),
            name: 'Original Name',
            bio: 'Original bio',
            userId: $userId,
        );
        $repo->save($member);

        $client->request('PUT', '/api/v1/me/club-profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['name' => 'Updated Name', 'bio' => 'Updated bio']));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['data']['updated']);

        $updated = $repo->findByUserId($userId);
        self::assertSame('Updated Name', $updated->name());
        self::assertSame('Updated bio', $updated->bio());
    }

    public function testChangePasswordWithValidCurrentPassword(): void
    {
        [$client] = $this->createAuthenticatedClient();

        $client->request('PUT', '/api/v1/me/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currentPassword' => 'testpass',
            'newPassword' => 'newpassword123',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['data']['updated']);
    }

    public function testChangePasswordWithInvalidCurrentPassword(): void
    {
        [$client] = $this->createAuthenticatedClient();

        $client->request('PUT', '/api/v1/me/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currentPassword' => 'wrongpass',
            'newPassword' => 'newpassword123',
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testChangePasswordWithShortNewPassword(): void
    {
        [$client] = $this->createAuthenticatedClient();

        $client->request('PUT', '/api/v1/me/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currentPassword' => 'testpass',
            'newPassword' => '123',
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testMeEndpointsRequireAuth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/me/club-profile');
        $this->assertResponseStatusCodeSame(401);

        $client->request('PUT', '/api/v1/me/club-profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['bio' => 'test']));
        $this->assertResponseStatusCodeSame(401);

        $client->request('PUT', '/api/v1/me/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['currentPassword' => 'a', 'newPassword' => 'b']));
        $this->assertResponseStatusCodeSame(401);
    }
}
