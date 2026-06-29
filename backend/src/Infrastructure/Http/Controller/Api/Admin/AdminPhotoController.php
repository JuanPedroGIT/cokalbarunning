<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Media\Delete\DeletePhotoCommand;
use App\Application\Media\Query\GetAllPhotosQuery;
use App\Application\Media\Response\PhotoResponseDto;
use App\Application\Media\Update\UpdatePhotoCommand;
use App\Application\Media\Upload\UploadPhotoCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminPhotoController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
    ) {
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

    #[Route('/photos', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No file uploaded or invalid'], 400);
        }

        $raceEditionId = $request->request->get('raceEditionId');
        if (empty($raceEditionId)) {
            return $this->json(['error' => 'raceEditionId is required'], 400);
        }

        $command = new UploadPhotoCommand(
            tmpPath: $file->getPathname(),
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType() ?? 'image/jpeg',
            altText: $request->request->get('altText') ?: null,
            raceEditionId: $raceEditionId,
            isFeatured: (bool) $request->request->get('isFeatured', false),
            sortOrder: (int) $request->request->get('sortOrder', 0),
        );

        $this->commandBus->dispatch($command);

        return $this->json(['data' => ['created' => true]], 201);
    }

    #[Route('/photos/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $command = new UpdatePhotoCommand(
            id: $id,
            altText: array_key_exists('altText', $data) ? $data['altText'] : null,
            isFeatured: $data['isFeatured'] ?? null,
            sortOrder: $data['sortOrder'] ?? null,
            raceEditionId: array_key_exists('raceEditionId', $data) ? ($data['raceEditionId'] ?: null) : null,
        );

        $this->commandBus->dispatch($command);

        return $this->json(['data' => ['updated' => true]]);
    }

    #[Route('/photos/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeletePhotoCommand(id: $id));

        return $this->json(null, 204);
    }
}
