<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminBlogControllerTest extends WebTestCase
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
        $user->setRoles(['ROLE_EDITOR']);
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
        $client->request('GET', '/api/v1/admin/posts');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateAndListPost(): void
    {
        $client = $this->createAuthenticatedClient();
        $unique = uniqid();

        $client->request('POST', '/api/v1/admin/posts', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Blog Post '.$unique,
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'tag' => 'News',
            'publishedAt' => '2025-07-01',
            'coverImage' => 'cover.jpg',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);

        $client->request('GET', '/api/v1/admin/posts');
        $this->assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $list);
        $this->assertIsArray($list['data']);

        $titles = array_column($list['data'], 'title');
        $this->assertContains('Test Blog Post '.$unique, $titles);
    }

    public function testUpdatePost(): void
    {
        $client = $this->createAuthenticatedClient();
        $unique = uniqid();

        $client->request('POST', '/api/v1/admin/posts', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Original Title '.$unique,
            'excerpt' => 'Original excerpt',
            'content' => 'Original content',
            'tag' => 'News',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/admin/posts');
        $list = json_decode($client->getResponse()->getContent(), true);
        $postId = $list['data'][0]['id'];

        $client->request('PUT', '/api/v1/admin/posts/'.$postId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Updated Title '.$unique,
            'content' => 'Updated content',
            'tag' => 'Update',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['data']['updated']);
    }

    public function testDeletePost(): void
    {
        $client = $this->createAuthenticatedClient();
        $unique = uniqid();

        $client->request('POST', '/api/v1/admin/posts', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'To Delete '.$unique,
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'tag' => 'News',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/admin/posts');
        $list = json_decode($client->getResponse()->getContent(), true);
        $postId = $list['data'][0]['id'];

        $client->request('DELETE', '/api/v1/admin/posts/'.$postId);
        $this->assertResponseStatusCodeSame(204);
    }
}
