<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Results\Import\ImportResultsCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminResultController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {
    }

    #[Route('/editions/{id}/results/import', methods: ['POST'])]
    public function import(string $id, Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No CSV file uploaded or invalid'], 400);
        }

        try {
            $envelope = $this->commandBus->dispatch(new ImportResultsCommand(
                editionId: $id,
                csvPath: $file->getRealPath(),
            ));
            $result = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json(['data' => $result]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
