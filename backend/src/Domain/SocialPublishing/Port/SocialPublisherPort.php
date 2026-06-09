<?php

declare(strict_types=1);

namespace App\Domain\SocialPublishing\Port;

use App\Domain\Media\Entity\BlogPost;
use App\Domain\SocialPublishing\Entity\SocialPublishLog;

interface SocialPublisherPort
{
    /**
     * Envía un post a la red social indicada mediante el webhook de n8n.
     *
     * @throws \App\Domain\SocialPublishing\Exception\SocialPublishingException
     */
    public function publish(BlogPost $post, SocialPublishLog $log): void;
}
