<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;

final class CloudflareStreamProductVideoTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_cloudflare_stream_discriminator_type(): void
    {
        self::assertSame('cloudflare_stream', CloudflareStreamProductVideo::getType());
    }

    /**
     * @test
     */
    public function it_holds_the_uid_and_starts_out_not_ready(): void
    {
        $video = new CloudflareStreamProductVideo();

        self::assertNull($video->getUid());
        self::assertFalse($video->isReady());

        $video->setUid('video123');
        $video->setReady(true);

        self::assertSame('video123', $video->getUid());
        self::assertTrue($video->isReady());
    }
}
