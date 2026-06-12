<?php

declare(strict_types=1);

namespace App\Application\Media\Update;

use App\Domain\Media\Repository\BlogPostRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsMessageHandler]
final class UpdateBlogPostHandler
{
    public function __construct(
        private BlogPostRepositoryInterface $repository,
        private SluggerInterface $slugger,
    ) {
    }

    public function __invoke(UpdateBlogPostCommand $command): void
    {
        $post = $this->repository->findById($command->id);
        if (!$post) {
            throw new \InvalidArgumentException('Post not found');
        }

        $title = $command->title ?? $post->title();
        $slug = $command->title !== null
            ? $this->generateUniqueSlug($command->title, $post->id())
            : $post->slug();

        $post->update(
            title: $title,
            slug: $slug,
            excerpt: $command->excerpt ?? $post->excerpt(),
            content: $command->content ?? $post->content(),
            tag: $command->tag ?? $post->tag(),
            coverImage: $command->coverImage !== null ? ($command->coverImage ?: null) : $post->coverImage(),
        );

        if ($command->publishedAt !== null) {
            $post->updatePublishedAt($command->publishedAt !== '' ? new \DateTimeImmutable($command->publishedAt) : null);
        }

        if ($command->bannerEndAt !== null) {
            $post->updateBannerEndAt($command->bannerEndAt !== '' ? new \DateTimeImmutable($command->bannerEndAt) : null);
        }

        if ($command->priority !== null) {
            $this->repository->clearPriority($command->priority, $post->id());
            $post->updatePriority($command->priority);
        }

        if ($command->type !== null) {
            $post->updateType($command->type);
        }

        $this->repository->save($post);
    }

    private function generateUniqueSlug(string $title, string $excludeId): string
    {
        $baseSlug = (string) $this->slugger->slug($title)->lower();
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $existing = $this->repository->findBySlug($slug);
            if ($existing === null || $existing->id() === $excludeId) {
                return $slug;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }
}
