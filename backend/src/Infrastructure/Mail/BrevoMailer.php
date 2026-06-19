<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use Symfony\Component\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BrevoMailer
{
    private const API_URL = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $brevoApiKey,
        private readonly string $senderEmail,
        private readonly string $senderName,
    ) {
        if ($this->brevoApiKey === '') {
            throw new \InvalidArgumentException('La variable de entorno BREVO_API_KEY no esta configurada.');
        }
    }

    public function send(Email $email): void
    {
        $from = $this->resolveFrom($email);
        $to = $this->resolveTo($email);

        $payload = [
            'sender' => [
                'name' => $from->getName() ?: $this->senderName,
                'email' => $from->getAddress() ?: $this->senderEmail,
            ],
            'to' => $this->mapAddresses([$to]),
            'subject' => $email->getSubject(),
            'htmlContent' => $email->getHtmlBody(),
        ];

        $bccAddresses = $email->getBcc();
        if ($bccAddresses !== []) {
            $payload['bcc'] = $this->mapAddresses($bccAddresses);
        }

        $textBody = $email->getTextBody();
        if ($textBody !== null && $textBody !== '') {
            $payload['textContent'] = $textBody;
        }

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'api-key' => $this->brevoApiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new \RuntimeException(
                    sprintf('Brevo API error %d: %s', $statusCode, $response->getContent(false))
                );
            }
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException(
                sprintf('Error de conexion con Brevo: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    private function resolveFrom(Email $email): Address
    {
        $addresses = $email->getFrom();

        if ($addresses === []) {
            return new Address($this->senderEmail, $this->senderName);
        }

        return $addresses[0];
    }

    private function resolveTo(Email $email): Address
    {
        $addresses = $email->getTo();

        if ($addresses === []) {
            throw new \InvalidArgumentException('El email no tiene destinatario (to).');
        }

        return $addresses[0];
    }

    /**
     * @return array<int, array{email: string, name?: string}>
     */
    private function mapAddresses(array $addresses): array
    {
        return array_map(static function (Address $address): array {
            $payload = ['email' => $address->getAddress()];
            $name = $address->getName();
            if ($name !== '') {
                $payload['name'] = $name;
            }

            return $payload;
        }, $addresses);
    }
}
