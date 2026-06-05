<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Media\Entity\BlogPost;
use App\Domain\Media\Repository\BlogPostRepositoryInterface;
use App\Entity\BlogPost as OrmBlogPost;
use App\Infrastructure\Persistence\Doctrine\Mapper\BlogPostMapper;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineBlogPostRepository implements BlogPostRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private BlogPostMapper $mapper,
    ) {
    }

    public function save(BlogPost $post): void
    {
        $existing = $this->em->getRepository(OrmBlogPost::class)->find($post->id());
        $orm = $this->mapper->toOrm($post, $existing);
        $this->em->persist($orm);
        $this->em->flush();
    }

    public function remove(BlogPost $post): void
    {
        $existing = $this->em->getRepository(OrmBlogPost::class)->find($post->id());
        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();
        }
    }

    public function findById(string $id): ?BlogPost
    {
        $orm = $this->em->getRepository(OrmBlogPost::class)->find($id);
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findBySlug(string $slug): ?BlogPost
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
            ->from(OrmBlogPost::class, 'p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug);

        $orm = $qb->getQuery()->getOneOrNullResult();
        if ($orm === null) {
            return null;
        }

        return $this->mapper->toDomain($orm);
    }

    public function findPublished(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
            ->from(OrmBlogPost::class, 'p')
            ->where('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC');

        return array_map(
            fn (OrmBlogPost $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findAll(): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
            ->from(OrmBlogPost::class, 'p')
            ->orderBy('p.createdAt', 'DESC');

        return array_map(
            fn (OrmBlogPost $orm) => $this->mapper->toDomain($orm),
            $qb->getQuery()->getResult()
        );
    }

    public function findLatestPublished(): ?BlogPost
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
            ->from(OrmBlogPost::class, 'p')
            ->where('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults(1);

        $orm = $qb->getQuery()->getOneOrNullResult();

        return $orm !== null ? $this->mapper->toDomain($orm) : null;
    }

    public function findByPriority(int $priority): ?BlogPost
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
            ->from(OrmBlogPost::class, 'p')
            ->where('p.priority = :priority')
            ->setParameter('priority', $priority)
            ->setMaxResults(1);

        $orm = $qb->getQuery()->getOneOrNullResult();

        return $orm !== null ? $this->mapper->toDomain($orm) : null;
    }

    public function findFeatured(): ?BlogPost
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('p')
            ->from(OrmBlogPost::class, 'p')
            ->where('p.priority = 1')
            ->andWhere('p.publishedAt IS NOT NULL')
            ->andWhere('p.publishedAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1);

        $orm = $qb->getQuery()->getOneOrNullResult();

        return $orm !== null ? $this->mapper->toDomain($orm) : null;
    }

    public function clearPriority(int $priority, ?string $excludeId = null): void
    {
        $qb = $this->em->createQueryBuilder();
        $qb->update(OrmBlogPost::class, 'p')
            ->set('p.priority', 'NULL')
            ->where('p.priority = :priority')
            ->setParameter('priority', $priority);

        if ($excludeId !== null) {
            $qb->andWhere('p.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        $qb->getQuery()->execute();
    }
}
