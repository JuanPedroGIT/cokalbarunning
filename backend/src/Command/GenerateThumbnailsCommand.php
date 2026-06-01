<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Service\PathGenerator;
use Aws\S3\S3Client;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:generate-thumbnails')]
final class GenerateThumbnailsCommand extends Command
{
    public function __construct(
        private StoragePort $storage,
        private Connection $connection,
        private S3Client $s3Client,
        private PathGenerator $pathGen,
        private string $bucket,
        private string $publicUrl,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate thumbnails and DB records for images already in R2')
            ->addArgument('year', InputArgument::REQUIRED, 'Edition year');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $year = (int) $input->getArgument('year');

        $edition = $this->connection->fetchAssociative(
            'SELECT id FROM race_editions WHERE year = ?', [$year]
        );

        if (!$edition) {
            $output->writeln("<error>Edition $year not found</error>");
            return Command::FAILURE;
        }

        $editionId = $edition['id'];
        $prefix = $this->pathGen->imagePrefix($year);
        $thumbPrefix = $this->pathGen->thumbPrefix($year);

        $output->writeln("Listing images in $prefix...");

        // List all objects with the images prefix
        $objects = [];
        $continuationToken = null;
        do {
            $args = [
                'Bucket' => $this->bucket,
                'Prefix' => $prefix,
                'MaxKeys' => 1000,
            ];
            if ($continuationToken) {
                $args['ContinuationToken'] = $continuationToken;
            }
            $result = $this->s3Client->listObjectsV2($args);
            foreach ($result['Contents'] ?? [] as $obj) {
                $key = $obj['Key'];
                if ($key !== $prefix && preg_match('/\.(jpg|jpeg|png|webp)$/i', $key)) {
                    $objects[] = $key;
                }
            }
            $continuationToken = $result['NextContinuationToken'] ?? null;
        } while ($continuationToken);

        $output->writeln("Found " . count($objects) . " images.");

        ini_set('memory_limit', '512M');
        $count = 0;
        $sortOrder = 0;
        foreach ($objects as $path) {
            // Check if already processed
            $existing = $this->connection->fetchAssociative(
                'SELECT id FROM photos WHERE original_path = ?', [$path]
            );
            if ($existing) {
                continue;
            }

            $filename = basename($path);
            $output->writeln("Processing $filename...");

            // Download image via public URL
            $imageUrl = $this->publicUrl . '/' . ltrim($path, '/');
            $imageData = @file_get_contents($imageUrl);
            if ($imageData === false) {
                $output->writeln("  <error>Cannot download</error>");
                continue;
            }

            $sourceImage = @imagecreatefromstring($imageData);
            if (!$sourceImage) {
                $output->writeln("  <error>Cannot read image</error>");
                continue;
            }

            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);
            $thumbWidth = 400;
            $thumbHeight = (int) round($origHeight * ($thumbWidth / $origWidth));

            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $origWidth, $origHeight);
            imagedestroy($sourceImage);

            $tempPath = sys_get_temp_dir() . '/' . Uuid::uuid4()->toString() . '.webp';
            imagewebp($thumb, $tempPath, 85);
            imagedestroy($thumb);

            $thumbData = file_get_contents($tempPath);
            @unlink($tempPath);

            $thumbFilename = $this->pathGen->thumbnailPath($year);
            $tempFile = tempnam(sys_get_temp_dir(), 'thumb_');
            file_put_contents($tempFile, $thumbData);

            $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                $tempFile, basename($thumbFilename), 'image/webp', null, true
            );

            $this->storage->store($uploadedFile, $thumbFilename);
            @unlink($tempFile);

            $photoId = Uuid::uuid4()->toString();
            $this->connection->executeStatement(
                'INSERT INTO photos (id, race_edition_id, original_path, thumb_path, alt_text, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$photoId, $editionId, $path, $thumbFilename, 'Foto ' . $filename, 0, $sortOrder++]
            );

            $count++;
            gc_collect_cycles();
            $output->writeln("  <info>✓ Thumbnail generated</info>");
        }

        $output->writeln("<info>Done! $count thumbnails generated for $year.</info>");
        return Command::SUCCESS;
    }
}
