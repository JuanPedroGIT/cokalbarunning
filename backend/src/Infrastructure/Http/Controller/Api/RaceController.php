<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Race\Query\GetActiveEditionQuery;
use App\Application\Race\Query\GetAllEditionsQuery;
use App\Application\Race\Query\GetEditionByYearQuery;
use App\Application\Race\Query\GetLatestEditionQuery;
use App\Application\Race\Response\RaceEditionResponseDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class RaceController extends AbstractController
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    #[Route('/editions', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetAllEditionsQuery());
        /** @var RaceEditionResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'data' => RaceEditionResponseDto::listToArray($dtos),
        ]);
    }

    #[Route('/editions/active', methods: ['GET'])]
    public function active(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetActiveEditionQuery());
        /** @var RaceEditionResponseDto|null $dto */
        $dto = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => $dto?->toArray() ?? null]);
    }

    #[Route('/editions/latest', methods: ['GET'])]
    public function latest(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetLatestEditionQuery());
        /** @var RaceEditionResponseDto|null $dto */
        $dto = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => $dto?->toArray() ?? null]);
    }

    #[Route('/editions/{year}', methods: ['GET'])]
    public function show(int $year): JsonResponse
    {
        try {
            $envelope = $this->queryBus->dispatch(new GetEditionByYearQuery(year: $year));
        } catch (\Symfony\Component\Messenger\Exception\HandlerFailedException $e) {
            $cause = $e->getPrevious();
            if ($cause instanceof \App\Domain\Shared\Exception\InvalidValueException) {
                return $this->json(['error' => $cause->getMessage()], 400);
            }
            throw $e;
        }
        /** @var RaceEditionResponseDto|null $dto */
        $dto = $envelope->last(HandledStamp::class)?->getResult();

        if ($dto === null) {
            return $this->json(['error' => 'Edition not found'], 404);
        }

        return $this->json(['data' => $dto->toArray()]);
    }
}
