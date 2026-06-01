<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class BlogControllerTest extends WebTestCase
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

    public function testListPublishedPosts(): void
    {
        $client = $this->createAuthenticatedClient();

        $unique = uniqid();

        // Create a published post
        $client->request('POST', '/api/v1/admin/posts', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Published Post '.$unique,
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'tag' => 'Test',
            'publishedAt' => '2020-01-01',
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Create a draft post
        $client->request('POST', '/api/v1/admin/posts', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Draft Post '.$unique,
            'excerpt' => 'Excerpt',
            'content' => 'Content',
            'tag' => 'Test',
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Public list should only show published
        $client->request('GET', '/api/v1/posts');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);

        $titles = array_column($data['data'], 'title');
        $this->assertContains('Published Post '.$unique, $titles);
        $this->assertNotContains('Draft Post '.$unique, $titles);
    }

    public function testShowPostBySlug(): void
    {
        $client = $this->createAuthenticatedClient();

        $unique = uniqid();

        $client->request('POST', '/api/v1/admin/posts', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Show Me Post '.$unique,
            'excerpt' => 'Excerpt',
            'content' => 'Full content here',
            'tag' => 'Test',
            'publishedAt' => '2020-01-01',
        ]));
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/posts/show-me-post-'.strtolower($unique));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertSame('Show Me Post '.$unique, $data['data']['title']);
        $this->assertSame('Full content here', $data['data']['content']);
    }

    public function testShowPostNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/posts/non-existent-slug');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }
}
