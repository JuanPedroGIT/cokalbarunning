<?php

declare(strict_types=1);

namespace App\Domain\Media\Service;

final class PathGenerator
{
    private const CLUB = 'un-nuevo-impulso';

    public function posterPath(int $year, string $ext): string
    {
        return self::CLUB . "/race/$year/docs/poster-" . \bin2hex(\random_bytes(16)) . ".$ext";
    }

    public function shirtPath(int $year, string $ext): string
    {
        return self::CLUB . "/race/$year/docs/camiseta-" . \bin2hex(\random_bytes(16)) . ".$ext";
    }

    public function photoPath(int $year, string $ext): string
    {
        return self::CLUB . "/race/$year/images/" . \bin2hex(\random_bytes(16)) . ".$ext";
    }

    public function thumbnailPath(int $year): string
    {
        return self::CLUB . "/race/$year/thumbnails/" . \bin2hex(\random_bytes(16)) . '.webp';
    }

    public function sponsorLogoPath(string $ext): string
    {
        return self::CLUB . '/sponsors/' . \bin2hex(\random_bytes(16)) . ".$ext";
    }

    public function clubMemberPhotoPath(string $ext): string
    {
        return self::CLUB . '/club/' . \bin2hex(\random_bytes(16)) . ".$ext";
    }

    public function documentPath(?int $year, string $type, string $ext): string
    {
        $folder = $type === 'results' ? 'results' : 'docs';
        if ($year !== null) {
            return self::CLUB . "/race/$year/$folder/" . \bin2hex(\random_bytes(16)) . ".$ext";
        }
        return self::CLUB . "/docs/" . \bin2hex(\random_bytes(16)) . ".$ext";
    }

    public function imagePrefix(int $year): string
    {
        return self::CLUB . "/race/$year/images/";
    }

    public function thumbPrefix(int $year): string
    {
        return self::CLUB . "/race/$year/thumbnails/";
    }

    public function blogCoverPath(string $ext): string
    {
        return self::CLUB . '/blog/' . \bin2hex(\random_bytes(16)) . ".$ext";
    }
}
