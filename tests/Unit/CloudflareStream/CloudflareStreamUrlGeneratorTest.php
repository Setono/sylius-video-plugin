<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\CloudflareStream;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamUrlGenerator;

final class CloudflareStreamUrlGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_builds_the_manifest_and_thumbnail_urls_on_the_customer_subdomain(): void
    {
        $generator = new CloudflareStreamUrlGenerator('abc123');

        self::assertSame('https://customer-abc123.cloudflarestream.com/video1/manifest/video.m3u8', $generator->hlsManifest('video1'));
        self::assertSame('https://customer-abc123.cloudflarestream.com/video1/manifest/video.mpd', $generator->dashManifest('video1'));
        self::assertSame('https://customer-abc123.cloudflarestream.com/video1/thumbnails/thumbnail.jpg?time=1s&height=720', $generator->thumbnail('video1'));
        self::assertSame('https://customer-abc123.cloudflarestream.com/video1/thumbnails/thumbnail.jpg?time=2m30s&height=360', $generator->thumbnail('video1', '2m30s', 360));
    }

    /**
     * @test
     *
     * @dataProvider customerSubdomainSpellings
     */
    public function it_accepts_the_customer_code_in_every_spelling_the_dashboard_shows(string $configured): void
    {
        $generator = new CloudflareStreamUrlGenerator($configured);

        self::assertSame('https://customer-abc123.cloudflarestream.com/video1/manifest/video.m3u8', $generator->hlsManifest('video1'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function customerSubdomainSpellings(): iterable
    {
        yield 'code' => ['abc123'];
        yield 'subdomain label' => ['customer-abc123'];
        yield 'full host' => ['customer-abc123.cloudflarestream.com'];
        yield 'padded' => [' customer-abc123.cloudflarestream.com '];
    }
}
