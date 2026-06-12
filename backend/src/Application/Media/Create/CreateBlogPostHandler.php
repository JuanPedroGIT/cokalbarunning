<?php

declare(strict_types=1);

namespace App\Application\Media\Create;

use App\Domain\Media\Entity\BlogPost;
use App\Domain\Media\Repository\BlogPostRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsMessageHandler]
final class CreateBlogPostHandler
{
    public function __construct(
        private BlogPostRepositoryInterface $repository,
        private SluggerInterface $slugger,
    ) {
    }

    public function __invoke(CreateBlogPostCommand $command): string
    {
        $id = Uuid::uuid4()->toString();
        $slug = $this->generateUniqueSlug($command->title);
        $post = new BlogPost(
            id: $id,
            title: $command->title,
            slug: $slug,
            excerpt: $command->excerpt,
            content: $command->content,
            tag: $command->tag,
            createdAt: new \DateTimeImmutable(),
            publishedAt: $command->publishedAt ? new \DateTimeImmutable($command->publishedAt) : null,
            bannerEndAt: $command->bannerEndAt ? new \DateTimeImmutable($command->bannerEndAt) : null,
            coverImage: $command->coverImage ?: null,
            priority: $command->priority,
            type: $command->type,
        );

        if ($command->priority !== null) {
            $this->repository->clearPriority($command->priority, $id);
        }

        $this->repository->save($post);

        return $id;
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = (string) $this->slugger->slug($title)->lower();
        $slug = $baseSlug;
        $counter = 1;

        while ($this->repository->findBySlug($slug) !== null) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
