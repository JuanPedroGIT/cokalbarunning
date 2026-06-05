<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Media\Entity\BlogPost as DomainBlogPost;
use App\Entity\BlogPost as OrmBlogPost;

final class BlogPostMapper
{
    public function toDomain(OrmBlogPost $orm): DomainBlogPost
    {
        return new DomainBlogPost(
            id: $orm->getId(),
            title: $orm->getTitle(),
            slug: $orm->getSlug(),
            excerpt: $orm->getExcerpt(),
            content: $orm->getContent(),
            tag: $orm->getTag(),
            publishedAt: $orm->getPublishedAt(),
            coverImage: $orm->getCoverImage(),
            priority: $orm->getPriority(),
            createdAt: $orm->getCreatedAt(),
        );
    }

    public function toOrm(DomainBlogPost $domain, ?OrmBlogPost $orm = null): OrmBlogPost
    {
        $target = $orm ?? new OrmBlogPost();
        $target->setId($domain->id());
        $target->setTitle($domain->title());
        $target->setSlug($domain->slug());
        $target->setExcerpt($domain->excerpt());
        $target->setContent($domain->content());
        $target->setTag($domain->tag());
        $target->setPublishedAt($domain->publishedAt());
        $target->setCoverImage($domain->coverImage());
        $target->setPriority($domain->priority());

        if ($orm === null) {
            $target->setCreatedAt($domain->createdAt());
        }

        return $target;
    }
}
