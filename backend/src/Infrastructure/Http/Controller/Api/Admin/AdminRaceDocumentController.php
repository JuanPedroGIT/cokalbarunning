<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Race\Delete\DeleteRaceDocumentCommand;
use App\Application\Race\Query\GetDocumentsByEditionQuery;
use App\Application\Race\Query\GetGeneralDocumentsQuery;
use App\Application\Race\Response\RaceDocumentResponseDto;
use App\Application\Race\Update\UpdateRaceDocumentCommand;
use App\Application\Race\UploadDocument\UploadRaceDocumentCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminRaceDocumentController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
    ) {
    }

    #[Route('/documents', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $editionId = $request->query->get('editionId');
        if ($editionId) {
            $envelope = $this->queryBus->dispatch(new GetDocumentsByEditionQuery(editionId: (string) $editionId));
        } else {
            $envelope = $this->queryBus->dispatch(new GetGeneralDocumentsQuery());
        }
        /** @var RaceDocumentResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'data' => RaceDocumentResponseDto::listToArray($dtos),
        ]);
    }

    #[Route('/documents', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No file uploaded or invalid'], 400);
        }

        $command = new UploadRaceDocumentCommand(
            tmpPath: $file->getPathname(),
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType() ?? 'application/pdf',
            name: $request->request->get('name', 'Documento sin nombre'),
            type: $request->request->get('type', 'general'),
            editionId: $request->request->get('editionId') ?: null,
        );

        $this->commandBus->dispatch($command);

        return $this->json(['data' => ['created' => true]], 201);
    }

    #[Route('/documents/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $command = new UpdateRaceDocumentCommand(
            id: $id,
            name: $data['name'] ?? null,
            type: $data['type'] ?? null,
            editionId: array_key_exists('editionId', $data) ? ($data['editionId'] ?: null) : null,
        );

        $this->commandBus->dispatch($command);

        return $this->json(['data' => ['updated' => true]]);
    }

    #[Route('/documents/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteRaceDocumentCommand(id: $id));

        return $this->json(null, 204);
    }
}
