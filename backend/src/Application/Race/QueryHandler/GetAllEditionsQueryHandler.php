<?php

declare(strict_types=1);

namespace App\Application\Race\QueryHandler;

use App\Application\Race\Query\GetAllEditionsQuery;
use App\Application\Race\Response\RaceEditionResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Race\Repository\RaceDocumentRepositoryInterface;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetAllEditionsQueryHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $repository,
        private RaceDocumentRepositoryInterface $documentRepository,
        private StoragePort $storage,
    ) {
    }

    /**
     * @return RaceEditionResponseDto[]
     */
    public function __invoke(GetAllEditionsQuery $query): array
    {
        $editions = $this->repository->findAllOrdered();
        $dtos = [];

        foreach ($editions as $edition) {
            $resultsUrl = null;
            $resultsDocumentId = null;
            $docs = $this->documentRepository->findByEditionId($edition->id()->value());
            foreach ($docs as $doc) {
                if ($doc->type()->value() === 'results') {
                    $resultsUrl = $this->storage->url($doc->filePath());
                    $resultsDocumentId = $doc->id();
                    break;
                }
            }

            $dtos[] = new RaceEditionResponseDto(
                id: $edition->id()->value(),
                year: $edition->year()->value(),
                name: $edition->name(),
                date: $edition->date()->format('Y-m-d'),
                location: $edition->location(),
                isActive: $edition->isActive(),
                showBibSearch: $edition->showBibSearch(),
                posterUrl: $edition->posterUrl() !== null ? $this->storage->url($edition->posterUrl()) : null,
                registrationUrl: $edition->registrationUrl(),
                shirtUrl: $edition->shirtUrl() !== null ? $this->storage->url($edition->shirtUrl()) : null,
                trophyUrl: $edition->trophyUrl() !== null ? $this->storage->url($edition->trophyUrl()) : null,
                description: $edition->description(),
                resultsUrl: $resultsUrl,
                resultsDocumentId: $resultsDocumentId,
                inscriptionInfo: $edition->inscriptionInfo(),
                solidarityCause: $edition->solidarityCause(),
                solidarityUrl: $edition->solidarityUrl(),
            );
        }

        return $dtos;
    }
}
