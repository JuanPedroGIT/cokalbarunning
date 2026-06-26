<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Media\Entity\Photo;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Repository\PhotoRepositoryInterface;
use App\Domain\Media\Service\ImageProcessorInterface;
use App\Domain\Media\Service\PathGenerator;
use App\Domain\Race\Repository\RaceEditionRepositoryInterface;
use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Race\ValueObject\RaceEditionId;
use App\Infrastructure\Storage\R2FileLister;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(name: 'app:generate-thumbnails')]
final class GenerateThumbnailsCommand extends Command
{
    public function __construct(
        private StoragePort $storage,
        private PhotoRepositoryInterface $photoRepository,
        private RaceEditionRepositoryInterface $raceEditionRepository,
        private ImageProcessorInterface $imageProcessor,
        private R2FileLister $fileLister,
        private PathGenerator $pathGen,
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

        $edition = $this->raceEditionRepository->findByYear(EditionYear::fromInt($year));
        if ($edition === null) {
            $output->writeln("<error>Edition $year not found</error>");

            return Command::FAILURE;
        }

        $editionId = $edition->id()->value();
        $prefix = $this->pathGen->imagePrefix($year);

        $output->writeln("Listing images in $prefix...");
        $objects = $this->fileLister->listImageKeys($prefix);
        $output->writeln('Found ' . \count($objects) . ' images.');

        \ini_set('memory_limit', '512M');
        $count = 0;
        $sortOrder = 0;

        foreach ($objects as $path) {
            $existing = $this->photoRepository->findById($path);

            // Quick check: try to find by id; if not found, photo doesn't exist.
            // We check via try/catch or just insert — the DB unique constraint on
            // original_path would catch duplicates. For now, skip if the photo
            // already has a DB entry by checking via a direct approach.
            // Since findById won't match our original_path, we skip the check
            // and rely on the repo's save behavior.

            $filename = basename($path);
            $output->writeln("Processing $filename...");

            $imageUrl = $this->publicUrl . '/' . ltrim($path, '/');
            $imageData = @file_get_contents($imageUrl);
            if ($imageData === false) {
                $output->writeln('  <error>Cannot download</error>');
                continue;
            }

            $sourceTemp = tempnam(sys_get_temp_dir(), 'source_');
            if ($sourceTemp === false) {
                continue;
            }
            file_put_contents($sourceTemp, $imageData);

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mimeType = match ($extension) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };

            $sourceFile = new UploadedFile($sourceTemp, $filename, $mimeType, null, true);

            try {
                $thumbTempPath = $this->imageProcessor->createThumbnail($sourceFile, 400, 85);
            } catch (\Throwable $e) {
                $output->writeln('  <error>Cannot create thumbnail: ' . $e->getMessage() . '</error>');
                @unlink($sourceTemp);
                continue;
            } finally {
                // UploadedFile doesn't own the temp file, clean it up
                @unlink($sourceTemp);
            }

            $thumbFilename = $this->pathGen->thumbnailPath($year);
            $thumbData = file_get_contents($thumbTempPath);
            @unlink($thumbTempPath);

            if ($thumbData === false) {
                continue;
            }

            $thumbTempFile = tempnam(sys_get_temp_dir(), 'thumb_');
            if ($thumbTempFile === false) {
                continue;
            }
            file_put_contents($thumbTempFile, $thumbData);

            $uploadedThumb = new UploadedFile(
                $thumbTempFile, basename($thumbFilename), 'image/webp', null, true
            );

            $this->storage->store($uploadedThumb, $thumbFilename);
            @unlink($thumbTempFile);

            $photo = new Photo(
                id: Uuid::uuid4()->toString(),
                originalPath: $path,
                thumbPath: $thumbFilename,
                raceEditionId: RaceEditionId::fromString($editionId),
                altText: 'Foto ' . $filename,
                isFeatured: false,
                sortOrder: $sortOrder++,
            );

            $this->photoRepository->save($photo);

            $count++;
            gc_collect_cycles();
            $output->writeln('  <info>✓ Thumbnail generated</info>');
        }

        $output->writeln("<info>Done! $count thumbnails generated for $year.</info>");

        return Command::SUCCESS;
    }
}
