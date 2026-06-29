<?php

declare(strict_types=1);

namespace App\Application\Media\UploadCover;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Service\PathGenerator;
use App\Entity\BlogPost;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UploadBlogCoverHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private StoragePort $storage,
        private PathGenerator $pathGen,
    ) {
    }

    public function __invoke(UploadBlogCoverCommand $command): string
    {
        $post = $this->em->getRepository(BlogPost::class)->find($command->postId);
        if (!$post) {
            throw new \RuntimeException('Blog post not found');
        }

        $file = new UploadedFile(
            $command->tmpPath,
            $command->originalName,
            $command->mimeType,
            null,
            true
        );

        $ext = $file->guessExtension() ?: 'jpg';
        $path = $this->pathGen->blogCoverPath($ext);

        if ($post->getCoverImage()) {
            $this->storage->delete($post->getCoverImage());
        }

        $this->storage->store($file, $path);
        $post->setCoverImage($path);
        $this->em->flush();

        return $this->storage->url($path);
    }
}
