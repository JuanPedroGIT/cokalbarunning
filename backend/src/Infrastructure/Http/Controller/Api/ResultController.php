<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Results\Query\GetResultsByYearQuery;
use App\Application\Results\Response\ResultResponseDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class ResultController extends AbstractController
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    #[Route('/editions/{year}/results', methods: ['GET'])]
    public function list(int $year): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetResultsByYearQuery(year: $year));
        /** @var ResultResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'data' => ResultResponseDto::listToArray($dtos),
        ]);
    }
}
