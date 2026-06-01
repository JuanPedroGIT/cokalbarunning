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
        $post = new BlogPost(
            id: $id,
            title: $command->title,
            slug: (string) $this->slugger->slug($command->title)->lower(),
            excerpt: $command->excerpt,
            content: $command->content,
            tag: $command->tag,
            createdAt: new \DateTimeImmutable(),
            publishedAt: $command->publishedAt ? new \DateTimeImmutable($command->publishedAt) : null,
            coverImage: $command->coverImage ?: null,
        );

        $this->repository->save($post);

        return $id;
    }
}
