<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Runner\Search\SearchRunnersQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class RunnerController extends AbstractController
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    #[Route('/runners', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $editionId = $request->query->get('editionId');
        $name = trim((string) $request->query->get('name'));

        if (!\is_string($editionId) || $editionId === '') {
            return $this->json(['error' => 'editionId is required'], 400);
        }

        if (mb_strlen($name) < 4) {
            return $this->json(['error' => 'name must be at least 4 characters'], 400);
        }

        $envelope = $this->queryBus->dispatch(new SearchRunnersQuery(
            editionId: $editionId,
            name: $name,
        ));

        $data = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => $data]);
    }
}
