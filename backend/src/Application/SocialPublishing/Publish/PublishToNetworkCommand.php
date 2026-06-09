<?php

declare(strict_types=1);

namespace App\Application\SocialPublishing\Publish;

final class PublishToNetworkCommand
{
    public function __construct(
        public string $postId,
        public string $network,
        public ?string $publishedBy = null,
    ) {
    }
}
