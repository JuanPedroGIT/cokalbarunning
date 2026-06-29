<?php

declare(strict_types=1);

namespace App\Application\Notification\GetGenericRecipients;

use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetGenericRecipientsQueryHandler
{
    public function __construct(
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(GetGenericRecipientsQuery $query): array
    {
        $activeEdition = $this->raceEditionRepository->findActive();
        $activeEditionId = $activeEdition?->id()->value();

        $conn = $this->entityManager->getConnection();

        $excludeJoin = '';
        $excludeWhere = '';
        if ($activeEditionId !== null) {
            $escapedId = $conn->quote($activeEditionId);
            $excludeJoin = "LEFT JOIN runners active_r ON active_r.email = r.email AND active_r.race_edition_id::uuid = $escapedId::uuid";
            $excludeWhere = ' AND active_r.id IS NULL';
        }

        $sql = <<<SQL
            SELECT DISTINCT ON (r.email)
                r.id, r.first_name, r.last_name, r.email, r.bib_number, r.club,
                r.gender, r.category, r.birth_date,
                re.year AS edition_year, re.name AS edition_name
            FROM runners r
            JOIN race_editions re ON re.id::uuid = r.race_edition_id::uuid
            $excludeJoin
            WHERE r.email IS NOT NULL AND r.email != ''
            $excludeWhere
            ORDER BY r.email, r.birth_date ASC NULLS LAST
        SQL;

        $rows = $conn->executeQuery($sql)->fetchAllAssociative();

        $items = array_map(function (array $row): array {
            $fullName = trim($row['first_name'] . ' ' . $row['last_name']);

            return [
                'firstName' => $row['first_name'],
                'lastName' => $row['last_name'],
                'fullName' => $fullName,
                'email' => $row['email'],
                'reference' => $row['bib_number'],
                'club' => $row['club'],
                'gender' => $row['gender'],
                'category' => $row['category'],
                'birthDate' => $row['birth_date'],
                'editionYear' => (int) $row['edition_year'],
                'editionName' => $row['edition_name'],
                'emailValid' => true,
            ];
        }, $rows);

        return [
            'items' => $items,
            'total' => \count($items),
        ];
    }
}
