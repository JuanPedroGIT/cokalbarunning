<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Registration\DeleteAllRunnersByEdition\DeleteAllRunnersByEditionCommand;
use App\Application\Registration\DeleteRunner\DeleteRunnerCommand;
use App\Application\Registration\GetRunnersByEdition\GetRunnersByEditionQuery;
use App\Application\Registration\ImportRunners\ImportRunnersFromCsvCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminRunnerController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
    ) {
    }

    #[Route('/editions/{id}/runners/import', methods: ['POST'])]
    public function import(string $id, Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'No CSV file uploaded or invalid'], 400);
        }

        $content = file_get_contents($file->getRealPath());
        if ($content === false) {
            return $this->json(['error' => 'Cannot read CSV file'], 400);
        }

        try {
            $envelope = $this->commandBus->dispatch(new ImportRunnersFromCsvCommand(
                raceEditionId: $id,
                csvContent: $content,
            ));
            $result = $envelope->last(HandledStamp::class)?->getResult();

            if ($result instanceof \App\Application\Registration\ImportRunners\ImportRunnersResultDto) {
                return $this->json(['data' => $result->toArray()]);
            }

            return $this->json(['data' => $result]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/editions/{id}/runners', methods: ['GET'])]
    public function list(string $id): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetRunnersByEditionQuery(editionId: $id));
        $data = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => $data]);
    }

    #[Route('/runners/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new DeleteRunnerCommand(id: $id));

            return $this->json(['data' => ['deleted' => true]]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/editions/{id}/runners', methods: ['DELETE'])]
    public function deleteAll(string $id): JsonResponse
    {
        try {
            $envelope = $this->commandBus->dispatch(new DeleteAllRunnersByEditionCommand(editionId: $id));
            $count = $envelope->last(HandledStamp::class)?->getResult() ?? 0;

            return $this->json(['data' => ['deleted' => $count]]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }
}
