<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Media\Query\GetActiveBannerQuery;
use App\Application\Media\Query\GetLatestPostQuery;
use App\Application\Media\Query\GetPostBySlugQuery;
use App\Application\Media\Query\GetPublishedPostsQuery;
use App\Application\Media\Response\BlogPostResponseDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class BlogController extends AbstractController
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    #[Route('/posts/featured', methods: ['GET'])]
    public function featured(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetLatestPostQuery());
        /** @var BlogPostResponseDto|null $dto */
        $dto = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => $dto?->toArray() ?? null]);
    }

    #[Route('/banner', methods: ['GET'])]
    public function banner(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetActiveBannerQuery());
        /** @var BlogPostResponseDto|null $dto */
        $dto = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => $dto?->toArray() ?? null]);
    }

    #[Route('/posts', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetPublishedPostsQuery());
        /** @var BlogPostResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'data' => BlogPostResponseDto::listToArray($dtos),
        ]);
    }

    #[Route('/posts/{slug}', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetPostBySlugQuery(slug: $slug));
        /** @var BlogPostResponseDto|null $dto */
        $dto = $envelope->last(HandledStamp::class)?->getResult();

        if ($dto === null) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        return $this->json(['data' => $dto->toArray()]);
    }
}
