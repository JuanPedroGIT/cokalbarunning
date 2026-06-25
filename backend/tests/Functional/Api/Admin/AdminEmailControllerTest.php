<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Admin;

use App\Entity\RaceEdition;
use App\Entity\Runner;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminEmailControllerTest extends WebTestCase
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
        $edition->setYear(2028);
        $edition->setName('Test Edition');
        $edition->setDescription('Test');
        $edition->setDate(new \DateTimeImmutable('2028-07-05'));
        $edition->setLocation('Coca de Alba');
        $edition->setIsActive(true);
        $edition->setShowBibSearch(false);

        $em->persist($edition);
        $em->flush();

        return $edition;
    }

    public function testListRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/admin/emails/raffle');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testPreviewCsv(): void
    {
        $client = $this->createAuthenticatedClient();
        $unique = uniqid();

        $csv = "Dorsal;Nombre;Apellidos;email\n001;Juan;Pérez;juan_{$unique}@example.com\n002;Ana;García;invalid-email-{$unique}";
        $tempFile = tempnam(sys_get_temp_dir(), 'bib_email_test_');
        file_put_contents($tempFile, $csv);

        $client->request('POST', '/api/v1/admin/emails/raffle/preview', [], [
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
        $this->assertSame('001', $data['data']['items'][0]['reference']);
    }

    public function testSendRaffleCreatesPendingLogsAndRunners(): void
    {
        $client = $this->createAuthenticatedClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $edition = $this->createRaceEdition($em);
        $unique = uniqid();

        $client->request('POST', '/api/v1/admin/emails/raffle/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'editionId' => $edition->getId(),
            'metadata' => [
                'title' => 'Sorteo de la camiseta',
                'description' => 'Participa en el sorteo.',
                'prize' => 'Camiseta oficial',
                'drawDate' => '5 de julio de 2028',
            ],
            'items' => [
                ['firstName' => 'Juan', 'lastName' => 'Pérez', 'fullName' => 'Juan Pérez', 'email' => "juan_{$unique}@example.com", 'reference' => '001', 'gender' => 'M', 'category' => 'VETERANO A'],
                ['firstName' => 'Ana', 'lastName' => 'García', 'fullName' => 'Ana García', 'email' => "ana_{$unique}@example.com", 'reference' => '002', 'gender' => 'F', 'category' => 'VETERANO B'],
            ],
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(2, $data['data']['queued']);
        $this->assertSame(0, $data['data']['skipped']);
        $this->assertSame(2, $data['data']['queuedInstructions']);

        $runner = $em->getRepository(Runner::class)->findOneBy(['email' => "juan_{$unique}@example.com"]);
        $this->assertNotNull($runner);
        $this->assertSame('Juan', $runner->getFirstName());
        $this->assertSame('Pérez', $runner->getLastName());
        $this->assertSame('001', $runner->getBibNumber());
        $this->assertSame('M', $runner->getGender());
        $this->assertSame('VETERANO A', $runner->getCategory());

        $raffleLog = $em->getRepository(\App\Entity\EmailSendLog::class)->findOneBy([
            'recipientEmail' => "juan_{$unique}@example.com",
            'type' => 'raffle',
        ]);
        $this->assertNotNull($raffleLog);
        $this->assertSame('001', $raffleLog->getReference());
        $this->assertSame('Sorteo de la camiseta', $raffleLog->getMetadata()['title']);

        $instructionsLog = $em->getRepository(\App\Entity\EmailSendLog::class)->findOneBy([
            'recipientEmail' => "juan_{$unique}@example.com",
            'type' => 'last_instructions',
        ]);
        $this->assertNotNull($instructionsLog);
        $this->assertSame('001', $instructionsLog->getReference());
    }

    public function testSendLastInstructionsCreatesRunners(): void
    {
        $client = $this->createAuthenticatedClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $edition = $this->createRaceEdition($em);
        $unique = uniqid();
        $email = "juan_{$unique}@example.com";

        $client->request('POST', '/api/v1/admin/emails/last_instructions/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'editionId' => $edition->getId(),
            'items' => [
                ['firstName' => 'Juan', 'lastName' => 'Pérez', 'fullName' => 'Juan Pérez', 'email' => $email, 'reference' => '001', 'gender' => 'M', 'category' => 'VETERANO A'],
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $runner = $em->getRepository(Runner::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($runner);
        $this->assertSame('001', $runner->getBibNumber());
        $this->assertSame('M', $runner->getGender());
        $this->assertSame('VETERANO A', $runner->getCategory());
    }

    public function testSendSkipsAlreadySentUnlessForced(): void
    {
        $client = $this->createAuthenticatedClient();
        $unique = uniqid();
        $email = "juan_{$unique}@example.com";

        $client->request('POST', '/api/v1/admin/emails/raffle/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'items' => [
                ['firstName' => 'Juan', 'lastName' => 'Pérez', 'fullName' => 'Juan Pérez', 'email' => $email, 'reference' => '001'],
            ],
            'metadata' => ['title' => 'Sorteo'],
        ]));

        $this->assertResponseIsSuccessful();

        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $log = $em->getRepository(\App\Entity\EmailSendLog::class)->findOneBy([
            'recipientEmail' => $email,
            'type' => 'raffle',
            'reference' => '001',
        ]);
        $this->assertNotNull($log);
        $log->markAsSent();
        $em->flush();

        $client->request('POST', '/api/v1/admin/emails/raffle/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'items' => [
                ['firstName' => 'Juan', 'lastName' => 'Pérez', 'fullName' => 'Juan Pérez', 'email' => $email, 'reference' => '001'],
            ],
            'metadata' => ['title' => 'Sorteo'],
        ]));
        $sendResponse = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(0, $sendResponse['data']['queued']);
        $this->assertSame(1, $sendResponse['data']['skipped']);

        $client->request('POST', '/api/v1/admin/emails/raffle/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'items' => [
                ['firstName' => 'Juan', 'lastName' => 'Pérez', 'fullName' => 'Juan Pérez', 'email' => $email, 'reference' => '001'],
            ],
            'metadata' => ['title' => 'Sorteo'],
            'force' => true,
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['data']['queued']);
        $this->assertSame(0, $data['data']['skipped']);
    }

    public function testUploadPrizeImageCreatesConfigAndStoresImage(): void
    {
        $client = $this->createAuthenticatedClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $edition = $this->createRaceEdition($em);

        $tempFile = tempnam(sys_get_temp_dir(), 'raffle_prize_') . '.jpg';
        $image = imagecreatetruecolor(100, 100);
        imagejpeg($image, $tempFile);
        imagedestroy($image);

        $client->request('POST', '/api/v1/admin/emails/raffle/prize-image', [
            'editionId' => $edition->getId(),
        ], [
            'file' => new \Symfony\Component\HttpFoundation\File\UploadedFile($tempFile, 'prize.jpg', 'image/jpeg'),
        ]);

        unlink($tempFile);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('prizeImageUrl', $data['data']);
        $this->assertStringContainsString('un-nuevo-impulso/raffle/', $data['data']['prizeImageUrl']);

        $config = $em->getRepository(\App\Entity\EmailConfig::class)->findOneBy([
            'raceEditionId' => $edition->getId(),
        ]);
        $this->assertNotNull($config);
        $this->assertStringContainsString('un-nuevo-impulso/raffle/', $config->getPrizeImageUrl());
    }

    public function testUpdateRaffleConfigNormalizesPrizeImageUrl(): void
    {
        $client = $this->createAuthenticatedClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $edition = $this->createRaceEdition($em);

        $client->request('POST', '/api/v1/admin/emails/raffle/config', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'editionId' => $edition->getId(),
            'title' => 'Sorteo test',
        ]));

        $createData = json_decode($client->getResponse()->getContent(), true);
        $configId = $createData['data']['id'];

        $client->request('PUT', "/api/v1/admin/emails/raffle/config/$configId", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'prizeImageUrl' => 'https://media.cokalba-running.com/un-nuevo-impulso/raffle/abc123.jpg',
        ]));

        $this->assertResponseIsSuccessful();

        $config = $em->getRepository(\App\Entity\EmailConfig::class)->find($configId);
        $this->assertSame('un-nuevo-impulso/raffle/abc123.jpg', $config->getPrizeImageUrl());

        $updateData = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('https://media.cokalba-running.com/un-nuevo-impulso/raffle/abc123.jpg', $updateData['data']['prizeImageUrl']);
    }

    public function testSendSkipsRunnersWithDuplicateBibNumber(): void
    {
        $client = $this->createAuthenticatedClient();
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        $edition = $this->createRaceEdition($em);
        $unique = uniqid();

        $client->request('POST', '/api/v1/admin/emails/raffle/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'editionId' => $edition->getId(),
            'metadata' => ['title' => 'Sorteo'],
            'items' => [
                ['firstName' => 'Juan', 'lastName' => 'Pérez', 'fullName' => 'Juan Pérez', 'email' => "juan_{$unique}@example.com", 'reference' => '001', 'gender' => 'M', 'category' => 'VETERANO A'],
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $client->request('POST', '/api/v1/admin/emails/raffle/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'editionId' => $edition->getId(),
            'metadata' => ['title' => 'Sorteo'],
            'items' => [
                ['firstName' => 'Otro', 'lastName' => 'Corredor', 'fullName' => 'Otro Corredor', 'email' => "otro_{$unique}@example.com", 'reference' => '001', 'gender' => 'F', 'category' => 'VETERANO B'],
            ],
        ]));

        $this->assertResponseIsSuccessful();

        $runner = $em->getRepository(Runner::class)->findOneBy([
            'email' => "otro_{$unique}@example.com",
            'raceEditionId' => $edition->getId(),
        ]);
        $this->assertNull($runner);

        $originalRunner = $em->getRepository(Runner::class)->findOneBy([
            'email' => "juan_{$unique}@example.com",
            'raceEditionId' => $edition->getId(),
        ]);
        $this->assertNotNull($originalRunner);
        $this->assertSame('Juan', $originalRunner->getFirstName());
        $this->assertSame('001', $originalRunner->getBibNumber());
    }
}
