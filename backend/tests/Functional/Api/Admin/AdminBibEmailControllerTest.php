<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminBibEmailControllerTest extends WebTestCase
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
        $client->request('GET', '/api/v1/admin/bib-emails');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testPreviewCsv(): void
    {
        $client = $this->createAuthenticatedClient();
        $unique = uniqid();

        $csv = "nombre;email;dorsal\nJuan Pérez;juan_{$unique}@example.com;001\nAna García;invalid-email-{$unique};002";
        $tempFile = tempnam(sys_get_temp_dir(), 'bib_email_test_');
        file_put_contents($tempFile, $csv);

        $client->request('POST', '/api/v1/admin/bib-emails/preview', [], [
            'file' => new \Symfony\Component\HttpFoundation\File\UploadedFile($tempFile, 'dorsales.csv', 'text/csv'),
        ]);

        unlink($tempFile);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('items', $data['data']);
        $this->assertCount(2, $data['data']['items']);
        $this->assertTrue($data['data']['items'][0]['emailValid']);
        $this->assertFalse($data['data']['items'][1]['emailValid']);
        $this->assertSame('not_sent', $data['data']['items'][0]['status']);
    }

    public function testSendEndpointQueuesEmails(): void
    {
        $client = $this->createAuthenticatedClient();
        $unique = uniqid();

        $client->request('POST', '/api/v1/admin/bib-emails/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'items' => [
                ['name' => 'Juan Pérez', 'email' => "juan_{$unique}@example.com", 'bibNumber' => '001'],
                ['name' => 'Ana García', 'email' => "ana_{$unique}@example.com", 'bibNumber' => '002'],
            ],
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(2, $data['data']['queued']);
        $this->assertSame(0, $data['data']['skipped']);

        $client->request('GET', '/api/v1/admin/bib-emails');
        $list = json_decode($client->getResponse()->getContent(), true);

        $emails = array_column($list['data'], 'recipientEmail');
        $this->assertContains("juan_{$unique}@example.com", $emails);
        $this->assertContains("ana_{$unique}@example.com", $emails);
    }

    public function testSendSkipsAlreadySentUnlessForced(): void
    {
        $client = $this->createAuthenticatedClient();
        $unique = uniqid();
        $email = "juan_{$unique}@example.com";

        $client->request('POST', '/api/v1/admin/bib-emails/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'items' => [
                ['name' => 'Juan Pérez', 'email' => $email, 'bibNumber' => '001'],
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $client->request('POST', '/api/v1/admin/bib-emails/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'items' => [
                ['name' => 'Juan Pérez', 'email' => $email, 'bibNumber' => '001'],
            ],
        ]));
        $sendResponse = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(0, $sendResponse['data']['queued']);
        $this->assertSame(1, $sendResponse['data']['skipped']);

        $client->request('POST', '/api/v1/admin/bib-emails/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'items' => [
                ['name' => 'Juan Pérez', 'email' => $email, 'bibNumber' => '001'],
            ],
            'force' => true,
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['data']['queued']);
        $this->assertSame(0, $data['data']['skipped']);
    }
}
