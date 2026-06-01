<?php

declare(strict_types=1);

namespace App\Application\Media\Delete;

use App\Domain\Media\Repository\BlogPostRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteBlogPostHandler
{
    public function __construct(
        private BlogPostRepositoryInterface $repository,
    ) {
    }

    public function __invoke(DeleteBlogPostCommand $command): void
    {
        $post = $this->repository->findById($command->id);
        if (!$post) {
            throw new \InvalidArgumentException('Post not found');
        }

        $this->repository->remove($post);
    }
}
