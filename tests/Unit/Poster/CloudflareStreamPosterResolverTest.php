<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Poster;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamUrlGenerator;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Poster\CloudflareStreamPosterResolver;

final class CloudflareStreamPosterResolverTest extends TestCase
{
    /**
     * @test
     */
    public function it_supports_only_ready_cloudflare_stream_videos_with_a_uid(): void
    {
        $resolver = $this->resolver();

        $ready = new CloudflareStreamProductVideo();
        $ready->setUid('video123');
        $ready->setReady(true);

        $processing = new CloudflareStreamProductVideo();
        $processing->setUid('video123');

        $withoutUid = new CloudflareStreamProductVideo();
        $withoutUid->setReady(true);

        self::assertTrue($resolver->supports($ready));
        self::assertFalse($resolver->supports($processing));
        self::assertFalse($resolver->supports($withoutUid));
        self::assertFalse($resolver->supports(new UrlProductVideo()));
    }

    /**
     * @test
     */
    public function it_resolves_the_thumbnail_cloudflare_generates(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');
        $video->setReady(true);

        self::assertSame('https://customer-abc.cloudflarestream.com/video123/thumbnails/thumbnail.jpg?time=1s&height=720', $this->resolver()->resolve($video));
    }

    /**
     * @test
     */
    public function it_resolves_to_null_for_an_unsupported_video(): void
    {
        self::assertNull($this->resolver()->resolve(new CloudflareStreamProductVideo()));
        self::assertNull($this->resolver()->resolve(new UrlProductVideo()));
    }

    private function resolver(): CloudflareStreamPosterResolver
    {
        return new CloudflareStreamPosterResolver(new CloudflareStreamUrlGenerator('abc'));
    }
}
