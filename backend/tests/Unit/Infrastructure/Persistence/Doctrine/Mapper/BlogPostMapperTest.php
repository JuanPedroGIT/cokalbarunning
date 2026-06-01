<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Media\Entity\BlogPost as DomainBlogPost;
use App\Entity\BlogPost as OrmBlogPost;
use App\Infrastructure\Persistence\Doctrine\Mapper\BlogPostMapper;
use PHPUnit\Framework\TestCase;

final class BlogPostMapperTest extends TestCase
{
    private BlogPostMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new BlogPostMapper();
    }

    public function testToDomainMapsAllFields(): void
    {
        $orm = new OrmBlogPost();
        $orm->setId('550e8400-e29b-41d4-a716-446655440800');
        $orm->setTitle('Test Post');
        $orm->setSlug('test-post');
        $orm->setExcerpt('Excerpt text');
        $orm->setContent('Full content');
        $orm->setTag('News');
        $orm->setPublishedAt(new \DateTimeImmutable('2025-07-01 10:00:00'));
        $orm->setCoverImage('cover.jpg');

        $domain = $this->mapper->toDomain($orm);

        self::assertSame('550e8400-e29b-41d4-a716-446655440800', $domain->id());
        self::assertSame('Test Post', $domain->title());
        self::assertSame('test-post', $domain->slug());
        self::assertSame('Excerpt text', $domain->excerpt());
        self::assertSame('Full content', $domain->content());
        self::assertSame('News', $domain->tag());
        self::assertSame('2025-07-01 10:00:00', $domain->publishedAt()?->format('Y-m-d H:i:s'));
        self::assertSame('cover.jpg', $domain->coverImage());
        self::assertTrue($domain->isPublished());
    }

    public function testToDomainHandlesNullables(): void
    {
        $orm = new OrmBlogPost();
        $orm->setId('550e8400-e29b-41d4-a716-446655440801');
        $orm->setTitle('Draft Post');
        $orm->setSlug('draft-post');
        $orm->setExcerpt('Draft');
        $orm->setContent('Content');
        $orm->setTag('General');

        $domain = $this->mapper->toDomain($orm);

        self::assertNull($domain->publishedAt());
        self::assertNull($domain->coverImage());
        self::assertFalse($domain->isPublished());
    }

    public function testToOrmCreatesNewEntity(): void
    {
        $domain = new DomainBlogPost(
            id: '550e8400-e29b-41d4-a716-446655440802',
            title: 'New Post',
            slug: 'new-post',
            excerpt: 'New excerpt',
            content: 'New content',
            tag: 'Update',
            publishedAt: new \DateTimeImmutable('2025-08-15'),
            coverImage: 'new-cover.jpg',
            createdAt: new \DateTimeImmutable('2025-01-01'),
        );

        $orm = $this->mapper->toOrm($domain);

        self::assertInstanceOf(OrmBlogPost::class, $orm);
        self::assertSame('New Post', $orm->getTitle());
        self::assertSame('new-post', $orm->getSlug());
        self::assertSame('New excerpt', $orm->getExcerpt());
        self::assertSame('New content', $orm->getContent());
        self::assertSame('Update', $orm->getTag());
        self::assertSame('2025-08-15', $orm->getPublishedAt()?->format('Y-m-d'));
        self::assertSame('new-cover.jpg', $orm->getCoverImage());
    }

    public function testToOrmUpdatesExistingEntity(): void
    {
        $existing = new OrmBlogPost();
        $existing->setId('550e8400-e29b-41d4-a716-446655440803');
        $existing->setTitle('Old Title');
        $existing->setSlug('old-slug');

        $domain = new DomainBlogPost(
            id: '550e8400-e29b-41d4-a716-446655440803',
            title: 'Updated Title',
            slug: 'updated-slug',
            excerpt: 'Updated excerpt',
            content: 'Updated content',
            tag: 'Updated',
            createdAt: new \DateTimeImmutable('2025-01-01'),
        );

        $orm = $this->mapper->toOrm($domain, $existing);

        self::assertSame($existing, $orm);
        self::assertSame('Updated Title', $orm->getTitle());
        self::assertSame('updated-slug', $orm->getSlug());
    }
}
