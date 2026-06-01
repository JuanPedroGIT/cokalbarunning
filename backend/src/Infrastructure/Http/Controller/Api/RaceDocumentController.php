<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Race\Query\GetDocumentsByEditionQuery;
use App\Application\Race\Query\GetGeneralDocumentsQuery;
use App\Application\Race\Response\RaceDocumentResponseDto;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\EditionYear;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class RaceDocumentController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
        private RaceEditionRepositoryInterface $raceEditionRepository,
    ) {
    }

    #[Route('/editions/{year}/documents', methods: ['GET'])]
    public function byEdition(int $year): JsonResponse
    {
        $edition = $this->raceEditionRepository->findByYear(EditionYear::fromInt($year));
        if (!$edition) {
            return $this->json(['data' => []], 200);
        }

        $envelope = $this->queryBus->dispatch(new GetDocumentsByEditionQuery(editionId: $edition->id()->value()));
        /** @var RaceDocumentResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'data' => RaceDocumentResponseDto::listToArray($dtos),
        ]);
    }

    #[Route('/documents', methods: ['GET'])]
    public function general(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetGeneralDocumentsQuery());
        /** @var RaceDocumentResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'data' => RaceDocumentResponseDto::listToArray($dtos),
        ]);
    }
}
