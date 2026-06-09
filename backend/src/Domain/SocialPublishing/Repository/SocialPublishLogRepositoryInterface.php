<?php

declare(strict_types=1);

namespace App\Domain\SocialPublishing\Repository;

use App\Domain\SocialPublishing\Entity\SocialPublishLog;

interface SocialPublishLogRepositoryInterface
{
    public function save(SocialPublishLog $log): void;

    public function findById(string $id): ?SocialPublishLog;

    public function findByPostAndNetwork(string $postId, string $network): ?SocialPublishLog;

    /** @return SocialPublishLog[] */
    public function findByPost(string $postId): array;

    /** @return SocialPublishLog[] */
    public function findAll(): array;
}
