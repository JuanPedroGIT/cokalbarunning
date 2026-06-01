<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SponsorControllerTest extends WebTestCase
{
    public function testListSponsors(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/sponsors');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }
}
