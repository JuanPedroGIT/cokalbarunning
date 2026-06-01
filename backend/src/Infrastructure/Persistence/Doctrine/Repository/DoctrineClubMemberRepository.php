<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Club\Entity\ClubMember;
use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use App\Entity\ClubMember as OrmMember;
use App\Infrastructure\Persistence\Doctrine\Mapper\ClubMemberMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineClubMemberRepository implements ClubMemberRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ClubMemberMapper $mapper,
    ) {
    }

    public function save(ClubMember $member): void
    {
        $existing = $this->em->getRepository(OrmMember::class)->find($member->id());
        $orm = $this->mapper->toOrm($member, $existing);
        $this->em->persist($orm);
        $this->em->flush();
    }

    public function remove(ClubMember $member): void
    {
        $existing = $this->em->getRepository(OrmMember::class)->find($member->id());
        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    public function findById(string $id): ?ClubMember
    {
        $orm = $this->em->getRepository(OrmMember::class)->find($id);
        return $orm !== null ? $this->mapper->toDomain($orm) : null;
    }

    public function findAllActive(): array
    {
        return array_map(
            fn (OrmMember $orm) => $this->mapper->toDomain($orm),
            $this->em->getRepository(OrmMember::class)->findBy(['isActive' => true], ['sortOrder' => 'ASC'])
        );
    }

    public function findAll(): array
    {
        return array_map(
            fn (OrmMember $orm) => $this->mapper->toDomain($orm),
            $this->em->getRepository(OrmMember::class)->findBy([], ['sortOrder' => 'ASC'])
        );
    }
}
