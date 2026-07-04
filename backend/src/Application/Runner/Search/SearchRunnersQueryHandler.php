<?php

declare(strict_types=1);

namespace App\Application\Runner\Search;

use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Entity\Runner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SearchRunnersQueryHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RaceEditionRepositoryInterface $raceEditionRepository,
    ) {
    }

    public function __invoke(SearchRunnersQuery $query): array
    {
        $editionId = $query->editionId;
        if ($editionId === null) {
            $activeEdition = $this->raceEditionRepository->findActive();
            if ($activeEdition === null) {
                return [];
            }
            $editionId = $activeEdition->id()->value();
        }

        $qb = $this->entityManager->getRepository(Runner::class)
            ->createQueryBuilder('r')
            ->where('r.raceEditionId = :editionId')
            ->setParameter('editionId', $editionId)
            ->andWhere('r.bibNumber IS NOT NULL')
            ->andWhere("r.bibNumber NOT IN ('0', '00', '000')");

        $hasFilter = false;

        if ($query->name !== null && $query->name !== '') {
            $term = mb_strtolower($query->name);
            $qb->andWhere(
                'LOWER(r.firstName) LIKE :name OR LOWER(r.lastName) LIKE :name OR LOWER(CONCAT(r.firstName, \' \', r.lastName)) LIKE :name'
            )
            ->setParameter('name', '%' . $term . '%');
            $hasFilter = true;
        }

        if ($query->bib !== null && $query->bib !== '') {
            $qb->andWhere('r.bibNumber LIKE :bib')
               ->setParameter('bib', '%' . $query->bib . '%');
            $hasFilter = true;
        }

        $qb
            ->orderBy('r.firstName', 'ASC')
            ->addOrderBy('r.lastName', 'ASC')
            ->setMaxResults($hasFilter ? 50 : 500);

        $runners = $qb->getQuery()->getResult();

        return array_map(static function (Runner $runner) {
            return [
                'id' => $runner->getId(),
                'firstName' => $runner->getFirstName(),
                'lastName' => $runner->getLastName(),
                'fullName' => trim($runner->getFirstName() . ' ' . $runner->getLastName()),
                'bibNumber' => $runner->getBibNumber(),
                'club' => $runner->getClub(),
                'gender' => $runner->getGender(),
                'category' => $runner->getCategory(),
            ];
        }, $runners);
    }
}
