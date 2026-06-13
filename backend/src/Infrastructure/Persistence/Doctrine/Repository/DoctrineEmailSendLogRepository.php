<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Notification\Entity\EmailSendLog as DomainEmailSendLog;
use App\Domain\Notification\Repository\EmailSendLogRepositoryInterface;
use App\Entity\EmailSendLog as OrmEmailSendLog;
use App\Infrastructure\Persistence\Doctrine\Mapper\EmailSendLogMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineEmailSendLogRepository implements EmailSendLogRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailSendLogMapper $mapper,
    ) {
    }

    public function save(DomainEmailSendLog $log): void
    {
        $existing = $this->em->getRepository(OrmEmailSendLog::class)->find($log->id());
        $orm = $this->mapper->toOrm($log, $existing);

        $this->em->persist($orm);
        $this->em->flush();
    }

    public function findById(string $id): ?DomainEmailSendLog
    {
        $orm = $this->em->getRepository(OrmEmailSendLog::class)->find($id);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findByRaceEditionId(string $raceEditionId): array
    {
        $orms = $this->em->getRepository(OrmEmailSendLog::class)->findBy(
            ['raceEditionId' => $raceEditionId],
            ['createdAt' => 'DESC']
        );

        return array_map(fn (OrmEmailSendLog $orm) => $this->mapper->toDomain($orm), $orms);
    }

    public function findByEmailAndBibNumber(string $email, string $bibNumber): ?DomainEmailSendLog
    {
        $orm = $this->em->getRepository(OrmEmailSendLog::class)->findOneBy([
            'recipientEmail' => $email,
            'bibNumber' => $bibNumber,
        ]);

        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function countSentByEmail(string $email): int
    {
        return (int) $this->em->getRepository(OrmEmailSendLog::class)->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.recipientEmail = :email')
            ->andWhere('l.status = :status')
            ->setParameter('email', $email)
            ->setParameter('status', 'sent')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAll(): array
    {
        $orms = $this->em->getRepository(OrmEmailSendLog::class)->findBy([], ['createdAt' => 'DESC']);

        return array_map(fn (OrmEmailSendLog $orm) => $this->mapper->toDomain($orm), $orms);
    }
}
