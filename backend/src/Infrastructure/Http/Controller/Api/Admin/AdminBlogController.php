<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Media\Create\CreateBlogPostCommand;
use App\Application\Media\Delete\DeleteBlogPostCommand;
use App\Application\Media\Query\GetAllPostsQuery;
use App\Application\Media\Response\BlogPostResponseDto;
use App\Application\Media\Update\UpdateBlogPostCommand;
use App\Application\SocialPublishing\Publish\PublishToNetworkCommand;
use App\Application\SocialPublishing\Response\SocialPublishLogResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Service\PathGenerator;
use App\Domain\SocialPublishing\Exception\SocialPublishingException;
use App\Domain\SocialPublishing\Repository\SocialPublishLogRepositoryInterface;
use App\Entity\BlogPost;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminBlogController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private StoragePort $storage,
        private PathGenerator $pathGen,
        private EntityManagerInterface $em,
        private SocialPublishLogRepositoryInterface $socialLogRepository,
    ) {
    }

    #[Route('/posts', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetAllPostsQuery());
        /** @var BlogPostResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'data' => BlogPostResponseDto::listToArray($dtos),
        ]);
    }

    #[Route('/posts', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $command = new CreateBlogPostCommand(
            title: $data['title'],
            excerpt: $data['excerpt'] ?? '',
            content: $data['content'] ?? '',
            tag: $data['tag'] ?? 'General',
            publishedAt: $data['publishedAt'] ?? null,
            coverImage: $data['coverImage'] ?? null,
            priority: array_key_exists('priority', $data) ? ($data['priority'] !== null ? (int) $data['priority'] : null) : null,
        );

        $envelope = $this->commandBus->dispatch($command);
        $stamp = $envelope->last(HandledStamp::class);
        $newId = $stamp ? $stamp->getResult() : null;

        if ($newId) {
            $orm = $this->em->getRepository(BlogPost::class)->find($newId);
            if ($orm) { $orm->setCreatedBy($this->getUser()->getEmail()); $this->em->flush(); }
        }

        return $this->json(['data' => ['id' => $newId]], 201);
    }

    #[Route('/posts/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $command = new UpdateBlogPostCommand(
            id: $id,
            title: $data['title'] ?? null,
            excerpt: $data['excerpt'] ?? null,
            content: $data['content'] ?? null,
            tag: $data['tag'] ?? null,
            publishedAt: array_key_exists('publishedAt', $data) ? $data['publishedAt'] : null,
            coverImage: array_key_exists('coverImage', $data) ? $data['coverImage'] : null,
            priority: array_key_exists('priority', $data) ? ($data['priority'] !== null ? (int) $data['priority'] : null) : null,
        );

        $this->commandBus->dispatch($command);

        $orm = $this->em->getRepository(BlogPost::class)->find($id);
        if ($orm) { $orm->setUpdatedBy($this->getUser()->getEmail()); $this->em->flush(); }

        return $this->json(['data' => ['updated' => true]]);
    }

    #[Route('/posts/{id}/cover', methods: ['POST'])]
    public function uploadCover(string $id, Request $request): JsonResponse
    {
        $post = $this->em->getRepository(BlogPost::class)->find($id);
        if (!$post) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        $ext = $file->guessExtension() ?: 'jpg';
        $path = $this->pathGen->blogCoverPath($ext);

        if ($post->getCoverImage()) {
            $this->storage->delete($post->getCoverImage());
        }

        $this->storage->store($file, $path);
        $post->setCoverImage($path);
        $this->em->flush();

        return $this->json(['data' => ['coverUrl' => $this->storage->url($path)]]);
    }

    #[Route('/posts/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteBlogPostCommand(id: $id));

        return $this->json(null, 204);
    }

    #[Route('/posts/{id}/publish-instagram', methods: ['POST'])]
    public function publishInstagram(string $id): JsonResponse
    {
        $user = $this->getUser();
        $publishedBy = $user?->getUserIdentifier() ?? null;

        $command = new PublishToNetworkCommand(
            postId: $id,
            network: 'instagram',
            publishedBy: $publishedBy,
        );

        try {
            $envelope = $this->commandBus->dispatch($command);
            $stamp = $envelope->last(HandledStamp::class);
            $logId = $stamp ? $stamp->getResult() : null;
        } catch (SocialPublishingException $e) {
            $statusCode = match (true) {
                str_contains($e->getMessage(), 'no existe') => 404,
                str_contains($e->getMessage(), 'ya ha sido publicado') => 409,
                str_contains($e->getMessage(), 'imagen de portada') => 400,
                default => 502,
            };
            return $this->json(['error' => $e->getMessage()], $statusCode);
        }

        return $this->json(['data' => ['logId' => $logId, 'status' => 'pending']]);
    }

    #[Route('/social-publishes', methods: ['GET'])]
    public function listSocialPublishes(): JsonResponse
    {
        $logs = $this->socialLogRepository->findAll();
        $dtos = SocialPublishLogResponseDto::fromDomainList($logs);

        return $this->json([
            'data' => SocialPublishLogResponseDto::listToArray($dtos),
        ]);
    }
}
