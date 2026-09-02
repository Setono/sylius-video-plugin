<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;

final class FileProductVideoTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_file_discriminator_type(): void
    {
        self::assertSame('file', FileProductVideo::getType());
    }

    /**
     * @test
     */
    public function it_holds_a_stored_path(): void
    {
        $video = new FileProductVideo();

        self::assertNull($video->getPath());

        $video->setPath('video/a/b.mp4');

        self::assertSame('video/a/b.mp4', $video->getPath());
    }

    /**
     * @test
     */
    public function it_carries_a_pending_file(): void
    {
        $video = new FileProductVideo();

        self::assertFalse($video->hasFile());
        self::assertNull($video->getFile());

        $file = new \SplFileInfo(__FILE__);
        $video->setFile($file);

        self::assertTrue($video->hasFile());
        self::assertSame($file, $video->getFile());
    }
}
