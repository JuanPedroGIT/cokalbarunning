<?php

declare(strict_types=1);

namespace App\Domain\Media\Port;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface StoragePort
{
    public function store(UploadedFile $file, string $path): string;

    public function delete(string $path): void;

    public function url(string $path): string;
}
