<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Entity\RaceEdition;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminPhotoControllerTest extends WebTestCase
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

    private function createRaceEdition(EntityManagerInterface $em): RaceEdition
    {
        $edition = new RaceEdition();
        $edition->setId(Uuid::uuid4()->toString());
        $edition->setYear(2026);
        $edition->setName('Test Edition');
        $edition->setDescription('Test');
        $edition->setDate(new \DateTimeImmutable());
        $edition->setLocation('Test Location');
        $edition->setIsActive(true);

        $em->persist($edition);
        $em->flush();

        return $edition;
    }

    public function testListRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/admin/photos');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateAndListPhoto(): void
    {
        $client = $this->createAuthenticatedClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $edition = $this->createRaceEdition($em);

        // Clean up photos from previous test runs to avoid legacy data issues
        $em->getConnection()->executeStatement('DELETE FROM photos');

        $tempFile = tempnam(sys_get_temp_dir(), 'photo_') . '.jpg';
        $image = imagecreatetruecolor(100, 100);
        imagejpeg($image, $tempFile, 90);
        imagedestroy($image);

        $client->request('POST', '/api/v1/admin/photos', [
            'altText' => 'Test Photo',
            'isFeatured' => true,
            'sortOrder' => 1,
            'raceEditionId' => $edition->getId(),
        ], [
            'file' => new \Symfony\Component\HttpFoundation\File\UploadedFile($tempFile, 'test.jpg', 'image/jpeg', null, true),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertTrue($data['data']['created']);

        $client->request('GET', '/api/v1/admin/photos');
        $this->assertResponseIsSuccessful();
        $list = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $list);
        $this->assertIsArray($list['data']);
        $this->assertCount(1, $list['data']);

        // Verify path structure follows PRD
        $this->assertStringContainsString('un-nuevo-impulso/race/2026/images/', $list['data'][0]['originalUrl']);
        $this->assertStringContainsString('un-nuevo-impulso/race/2026/thumbnails/', $list['data'][0]['thumbUrl']);

        unlink($tempFile);
    }

    public function testUpdatePhoto(): void
    {
        $client = $this->createAuthenticatedClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $edition = $this->createRaceEdition($em);

        $tempFile = tempnam(sys_get_temp_dir(), 'photo_') . '.jpg';
        $image = imagecreatetruecolor(100, 100);
        imagejpeg($image, $tempFile, 90);
        imagedestroy($image);

        $client->request('POST', '/api/v1/admin/photos', [
            'altText' => 'Original Alt Text',
            'sortOrder' => 1,
            'raceEditionId' => $edition->getId(),
        ], [
            'file' => new \Symfony\Component\HttpFoundation\File\UploadedFile($tempFile, 'test.jpg', 'image/jpeg', null, true),
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/admin/photos');
        $list = json_decode($client->getResponse()->getContent(), true);
        $photoId = null;
        foreach ($list['data'] as $photo) {
            if ($photo['altText'] === 'Original Alt Text') {
                $photoId = $photo['id'];
                break;
            }
        }
        $this->assertNotNull($photoId, 'Created photo not found for update test');

        $client->request('PUT', '/api/v1/admin/photos/'.$photoId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'altText' => 'Updated Alt Text',
            'isFeatured' => true,
            'sortOrder' => 99,
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['data']['updated']);

        unlink($tempFile);
    }

    public function testDeletePhoto(): void
    {
        $client = $this->createAuthenticatedClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $edition = $this->createRaceEdition($em);

        $tempFile = tempnam(sys_get_temp_dir(), 'photo_') . '.jpg';
        $image = imagecreatetruecolor(100, 100);
        imagejpeg($image, $tempFile, 90);
        imagedestroy($image);

        $client->request('POST', '/api/v1/admin/photos', [
            'altText' => 'To Delete',
            'raceEditionId' => $edition->getId(),
        ], [
            'file' => new \Symfony\Component\HttpFoundation\File\UploadedFile($tempFile, 'test.jpg', 'image/jpeg', null, true),
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('GET', '/api/v1/admin/photos');
        $list = json_decode($client->getResponse()->getContent(), true);
        $photoId = null;
        foreach ($list['data'] as $photo) {
            if ($photo['altText'] === 'To Delete') {
                $photoId = $photo['id'];
                break;
            }
        }
        $this->assertNotNull($photoId, 'Created photo not found for delete test');

        $client->request('DELETE', '/api/v1/admin/photos/'.$photoId);
        $this->assertResponseStatusCodeSame(204);

        unlink($tempFile);
    }
}
