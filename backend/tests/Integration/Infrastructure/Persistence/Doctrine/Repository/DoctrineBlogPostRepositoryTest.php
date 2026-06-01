<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Media\Entity\BlogPost;
use App\Infrastructure\Persistence\Doctrine\Repository\DoctrineBlogPostRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineBlogPostRepositoryTest extends KernelTestCase
{
    private DoctrineBlogPostRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(DoctrineBlogPostRepository::class);
    }

    public function testSaveAndFindById(): void
    {
        $slug = 'integration-post-'.uniqid();
        $post = new BlogPost(
            id: Uuid::uuid4()->toString(),
            title: 'Integration Post',
            slug: $slug,
            excerpt: 'Test excerpt',
            content: 'Test content',
            tag: 'Test',
            createdAt: new \DateTimeImmutable('2025-01-01'),
            publishedAt: new \DateTimeImmutable('2025-07-01'),
            coverImage: 'cover.jpg',
        );

        $this->repository->save($post);

        $found = $this->repository->findById($post->id());

        self::assertNotNull($found);
        self::assertSame('Integration Post', $found->title());
        self::assertSame($slug, $found->slug());
    }

    public function testFindByIdReturnsNullForMissing(): void
    {
        $found = $this->repository->findById(Uuid::uuid4()->toString());
        self::assertNull($found);
    }

    public function testFindBySlug(): void
    {
        $id = Uuid::uuid4()->toString();
        $slug = 'unique-slug-test-'.uniqid();
        $post = new BlogPost(
            id: $id,
            title: 'Slug Test',
            slug: $slug,
            excerpt: 'Excerpt',
            content: 'Content',
            tag: 'Test',
            createdAt: new \DateTimeImmutable('2025-01-01'),
        );

        $this->repository->save($post);

        $found = $this->repository->findBySlug($slug);
        self::assertNotNull($found);
        self::assertSame('Slug Test', $found->title());
    }

    public function testFindPublishedReturnsOnlyPublished(): void
    {
        $suffix = uniqid();
        $published = new BlogPost(
            id: Uuid::uuid4()->toString(),
            title: 'Published Post',
            slug: 'published-post-'.$suffix,
            excerpt: 'Excerpt',
            content: 'Content',
            tag: 'Test',
            createdAt: new \DateTimeImmutable('2020-01-01'),
            publishedAt: new \DateTimeImmutable('2020-01-01'),
        );
        $draft = new BlogPost(
            id: Uuid::uuid4()->toString(),
            title: 'Draft Post',
            slug: 'draft-post-'.$suffix,
            excerpt: 'Excerpt',
            content: 'Content',
            tag: 'Test',
            createdAt: new \DateTimeImmutable('2025-01-01'),
        );

        $this->repository->save($published);
        $this->repository->save($draft);

        $results = $this->repository->findPublished();
        $titles = array_map(fn (BlogPost $p) => $p->title(), $results);

        self::assertContains('Published Post', $titles);
        self::assertNotContains('Draft Post', $titles);
    }

    public function testFindAllReturnsAll(): void
    {
        $post = new BlogPost(
            id: Uuid::uuid4()->toString(),
            title: 'All Posts Test',
            slug: 'all-posts-test-'.uniqid(),
            excerpt: 'Excerpt',
            content: 'Content',
            tag: 'Test',
            createdAt: new \DateTimeImmutable('2025-01-01'),
        );

        $this->repository->save($post);

        $all = $this->repository->findAll();
        $titles = array_map(fn (BlogPost $p) => $p->title(), $all);

        self::assertContains('All Posts Test', $titles);
    }

    public function testRemove(): void
    {
        $id = Uuid::uuid4()->toString();
        $post = new BlogPost(
            id: $id,
            title: 'To Delete',
            slug: 'to-delete-'.uniqid(),
            excerpt: 'Excerpt',
            content: 'Content',
            tag: 'Test',
            createdAt: new \DateTimeImmutable('2025-01-01'),
        );

        $this->repository->save($post);

        $found = $this->repository->findById($id);
        self::assertNotNull($found);

        $this->repository->remove($found);

        $deleted = $this->repository->findById($id);
        self::assertNull($deleted);
    }
}
