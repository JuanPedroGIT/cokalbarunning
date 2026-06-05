<?php

declare(strict_types=1);

namespace App\Domain\Media\Repository;

use App\Domain\Media\Entity\BlogPost;

interface BlogPostRepositoryInterface
{
    public function save(BlogPost $post): void;

    public function remove(BlogPost $post): void;

    public function findById(string $id): ?BlogPost;

    public function findBySlug(string $slug): ?BlogPost;

    /** @return BlogPost[] */
    public function findPublished(): array;

    /** @return BlogPost[] */
    public function findAll(): array;

    public function findLatestPublished(): ?BlogPost;

    public function findByPriority(int $priority): ?BlogPost;

    public function findFeatured(): ?BlogPost;

    /** Quita la prioridad de otros posts que tengan este valor, excepto el indicado. */
    public function clearPriority(int $priority, ?string $excludeId = null): void;
}
