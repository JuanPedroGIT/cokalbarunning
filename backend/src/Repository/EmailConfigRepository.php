<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailConfig>
 */
final class EmailConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailConfig::class);
    }

    public function save(EmailConfig $config): void
    {
        $this->getEntityManager()->persist($config);
        $this->getEntityManager()->flush();
    }

    public function findByRaceEditionIdAndType(string $raceEditionId, string $type): ?EmailConfig
    {
        return $this->findOneBy(['raceEditionId' => $raceEditionId, 'type' => $type]);
    }
}
