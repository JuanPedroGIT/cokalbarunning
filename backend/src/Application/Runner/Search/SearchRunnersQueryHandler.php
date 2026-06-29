<?php

declare(strict_types=1);

namespace App\Application\Runner\Search;

use App\Entity\Runner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SearchRunnersQueryHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SearchRunnersQuery $query): array
    {
        $term = mb_strtolower($query->name);

        $runners = $this->entityManager->getRepository(Runner::class)
            ->createQueryBuilder('r')
            ->where('r.raceEditionId = :editionId')
            ->setParameter('editionId', $query->editionId)
            ->andWhere('r.bibNumber IS NOT NULL')
            ->andWhere("r.bibNumber != ''")
            ->andWhere("TRIM(r.bibNumber, '0') != ''")
            ->andWhere(
                'LOWER(r.firstName) LIKE :name OR LOWER(r.lastName) LIKE :name OR LOWER(CONCAT(r.firstName, \' \', r.lastName)) LIKE :name'
            )
            ->setParameter('name', '%' . $term . '%')
            ->orderBy('r.firstName', 'ASC')
            ->addOrderBy('r.lastName', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

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
