<?php

declare(strict_types=1);

namespace App\Application\SocialPublishing\Publish;

use App\Domain\Media\Repository\BlogPostRepositoryInterface;
use App\Domain\SocialPublishing\Entity\SocialPublishLog;
use App\Domain\SocialPublishing\Exception\SocialPublishingException;
use App\Domain\SocialPublishing\Port\SocialPublisherPort;
use App\Domain\SocialPublishing\Repository\SocialPublishLogRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PublishToNetworkHandler
{
    public function __construct(
        private BlogPostRepositoryInterface $postRepository,
        private SocialPublishLogRepositoryInterface $logRepository,
        private SocialPublisherPort $publisher,
    ) {
    }

    public function __invoke(PublishToNetworkCommand $command): string
    {
        $post = $this->postRepository->findById($command->postId);
        if ($post === null) {
            throw SocialPublishingException::postNotFound();
        }

        $existing = $this->logRepository->findByPostAndNetwork($command->postId, $command->network);
        if ($existing !== null && $existing->status() === 'published') {
            throw SocialPublishingException::alreadyPublished($command->network);
        }

        if ($existing !== null) {
            $log = $existing;
            $log->resetToPending();
        } else {
            $log = new SocialPublishLog(
                id: Uuid::uuid4()->toString(),
                postId: $command->postId,
                network: $command->network,
                status: 'pending',
                createdAt: new \DateTimeImmutable(),
                publishedBy: $command->publishedBy,
            );
        }

        $this->logRepository->save($log);

        try {
            $this->publisher->publish($post, $log);
        } catch (SocialPublishingException $e) {
            $log->markAsFailed();
            $this->logRepository->save($log);
            throw $e;
        }

        return $log->id();
    }
}
