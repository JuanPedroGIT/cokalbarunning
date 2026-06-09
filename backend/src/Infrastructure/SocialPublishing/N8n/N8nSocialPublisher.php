<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialPublishing\N8n;

use App\Domain\Media\Entity\BlogPost;
use App\Domain\Media\Port\StoragePort;
use App\Domain\SocialPublishing\Entity\SocialPublishLog;
use App\Domain\SocialPublishing\Exception\SocialPublishingException;
use App\Domain\SocialPublishing\Port\SocialPublisherPort;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class N8nSocialPublisher implements SocialPublisherPort
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private StoragePort $storage,
        private LoggerInterface $logger,
        private string $webhookUrl,
        private string $authHeader,
        private string $authToken,
        private string $websiteUrl,
    ) {
    }

    public function publish(BlogPost $post, SocialPublishLog $log): void
    {
        $payload = [
            'id' => $post->id(),
            'titulo' => $post->title(),
            'texto' => $post->excerpt(),
            'url_noticia' => rtrim($this->websiteUrl, '/') . '/blog/' . $post->slug(),
            'url_imagen' => $post->coverImage() !== null ? $this->storage->url($post->coverImage()) : null,
            'network' => $log->network(),
            'log_id' => $log->id(),
        ];

        $headers = ['Content-Type' => 'application/json'];
        if ($this->authToken !== '') {
            $headers[$this->authHeader] = $this->authToken;
        }

        $this->logger->info('N8n webhook request', [
            'url' => $this->webhookUrl,
            'headers' => $headers,
            'has_auth_token' => $this->authToken !== '',
            'auth_token_length' => \strlen($this->authToken),
            'auth_header_name' => $this->authHeader,
        ]);

        try {
            $response = $this->httpClient->request('POST', $this->webhookUrl, [
                'headers' => $headers,
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $this->logger->info('N8n webhook response', ['status' => $statusCode]);

            if ($statusCode < 200 || $statusCode >= 300) {
                throw SocialPublishingException::webhookFailed(
                    sprintf('HTTP %d', $statusCode)
                );
            }
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $this->logger->error('N8n webhook transport error', ['error' => $e->getMessage()]);
            throw SocialPublishingException::webhookFailed($e->getMessage());
        } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
            $this->logger->error('N8n webhook client error', ['error' => $e->getMessage()]);
            throw SocialPublishingException::webhookFailed($e->getMessage());
        } catch (\Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface $e) {
            $this->logger->error('N8n webhook server error', ['error' => $e->getMessage()]);
            throw SocialPublishingException::webhookFailed($e->getMessage());
        }
    }
}
