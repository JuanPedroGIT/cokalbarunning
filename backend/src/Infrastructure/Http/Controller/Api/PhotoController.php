<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Media\Query\GetAllPhotosQuery;
use App\Application\Media\Query\GetFeaturedPhotosQuery;
use App\Application\Media\Response\PhotoResponseDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class PhotoController extends AbstractController
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    #[Route('/photos', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $editionId = $request->query->get('editionId');
        $envelope = $this->queryBus->dispatch(new GetAllPhotosQuery(editionId: $editionId));
        /** @var PhotoResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'data' => PhotoResponseDto::listToArray($dtos),
        ]);
    }

    #[Route('/photos/featured', methods: ['GET'])]
    public function featured(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetFeaturedPhotosQuery());
        /** @var PhotoResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'data' => PhotoResponseDto::listToArray($dtos),
        ]);
    }
}
