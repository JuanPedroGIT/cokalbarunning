<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Media\Service;

use App\Domain\Media\Service\PathGenerator;
use PHPUnit\Framework\TestCase;

final class PathGeneratorTest extends TestCase
{
    private PathGenerator $pathGen;

    protected function setUp(): void
    {
        $this->pathGen = new PathGenerator();
    }

    public function testPosterPathContainsYearAndExtension(): void
    {
        $path = $this->pathGen->posterPath(2026, 'jpg');

        $this->assertStringStartsWith('un-nuevo-impulso/race/2026/docs/poster-', $path);
        $this->assertStringEndsWith('.jpg', $path);
    }

    public function testShirtPathContainsYearAndExtension(): void
    {
        $path = $this->pathGen->shirtPath(2025, 'png');

        $this->assertStringStartsWith('un-nuevo-impulso/race/2025/docs/camiseta-', $path);
        $this->assertStringEndsWith('.png', $path);
    }

    public function testTrophyPathContainsYearAndExtension(): void
    {
        $path = $this->pathGen->trophyPath(2024, 'jpeg');

        $this->assertStringStartsWith('un-nuevo-impulso/race/2024/docs/trofeo-', $path);
        $this->assertStringEndsWith('.jpeg', $path);
    }

    public function testPhotoPathContainsYearAndExtension(): void
    {
        $path = $this->pathGen->photoPath(2026, 'webp');

        $this->assertStringStartsWith('un-nuevo-impulso/race/2026/images/', $path);
        $this->assertStringEndsWith('.webp', $path);
    }

    public function testThumbnailPathReturnsWebp(): void
    {
        $path = $this->pathGen->thumbnailPath(2026);

        $this->assertStringStartsWith('un-nuevo-impulso/race/2026/thumbnails/', $path);
        $this->assertStringEndsWith('.webp', $path);
    }

    public function testSponsorLogoPath(): void
    {
        $path = $this->pathGen->sponsorLogoPath('png');

        $this->assertStringStartsWith('un-nuevo-impulso/sponsors/', $path);
        $this->assertStringEndsWith('.png', $path);
    }

    public function testClubMemberPhotoPath(): void
    {
        $path = $this->pathGen->clubMemberPhotoPath('jpg');

        $this->assertStringStartsWith('un-nuevo-impulso/club/', $path);
        $this->assertStringEndsWith('.jpg', $path);
    }

    public function testDocumentPathWithYearAndTypeResults(): void
    {
        $path = $this->pathGen->documentPath(2026, 'results', 'pdf');

        $this->assertStringStartsWith('un-nuevo-impulso/race/2026/results/', $path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function testDocumentPathWithYearAndOtherType(): void
    {
        $path = $this->pathGen->documentPath(2026, 'route', 'pdf');

        $this->assertStringStartsWith('un-nuevo-impulso/race/2026/docs/', $path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function testDocumentPathWithoutYear(): void
    {
        $path = $this->pathGen->documentPath(null, 'other', 'pdf');

        $this->assertStringStartsWith('un-nuevo-impulso/docs/', $path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function testImagePrefix(): void
    {
        $prefix = $this->pathGen->imagePrefix(2026);

        $this->assertSame('un-nuevo-impulso/race/2026/images/', $prefix);
    }

    public function testThumbPrefix(): void
    {
        $prefix = $this->pathGen->thumbPrefix(2026);

        $this->assertSame('un-nuevo-impulso/race/2026/thumbnails/', $prefix);
    }

    public function testBlogCoverPath(): void
    {
        $path = $this->pathGen->blogCoverPath('jpg');

        $this->assertStringStartsWith('un-nuevo-impulso/blog/', $path);
        $this->assertStringEndsWith('.jpg', $path);
    }

    public function testEmailImagePathContainsType(): void
    {
        $path = $this->pathGen->emailImagePath('raffle', 'png');

        $this->assertStringStartsWith('un-nuevo-impulso/raffle/', $path);
        $this->assertStringEndsWith('.png', $path);
    }

    public function testEmailImagePathForLastInstructions(): void
    {
        $path = $this->pathGen->emailImagePath('last_instructions', 'jpg');

        $this->assertStringStartsWith('un-nuevo-impulso/last_instructions/', $path);
        $this->assertStringEndsWith('.jpg', $path);
    }
}
