<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamUrlGenerator;
use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Renderer\CloudflareStreamProductVideoRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class CloudflareStreamProductVideoRendererTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_supports_only_cloudflare_stream_videos(): void
    {
        $renderer = $this->renderer();

        self::assertTrue($renderer->supports(new CloudflareStreamProductVideo()));
        self::assertFalse($renderer->supports(new UrlProductVideo()));
    }

    /**
     * @test
     */
    public function it_renders_the_player_and_manifest_urls_of_a_ready_video(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');
        $video->setReady(true);

        self::assertSame(
            'https://customer-abc.cloudflarestream.com/video123/iframe|https://customer-abc.cloudflarestream.com/video123/manifest/video.m3u8|https://customer-abc.cloudflarestream.com/video123/manifest/video.mpd|video123',
            $this->renderer()->render($video),
        );
    }

    /**
     * @test
     */
    public function it_renders_nothing_while_the_video_is_being_processed(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');

        self::assertSame('', $this->renderer()->render($video));
    }

    /**
     * @test
     */
    public function it_renders_nothing_for_a_video_without_a_uid(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setReady(true);

        self::assertSame('', $this->renderer()->render($video));
    }

    /**
     * @test
     */
    public function it_uses_the_plugins_template_by_default(): void
    {
        $twig = $this->prophesize(Environment::class);
        $twig->render('@SetonoSyliusVideoPlugin/shop/renderer/cloudflare_stream.html.twig', \Prophecy\Argument::type('array'))->willReturn('rendered');

        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');
        $video->setReady(true);

        self::assertSame('rendered', (new CloudflareStreamProductVideoRenderer($twig->reveal(), new CloudflareStreamUrlGenerator('abc')))->render($video));
    }

    /**
     * @test
     */
    public function it_throws_when_asked_to_render_an_unsupported_video(): void
    {
        $this->expectException(UnsupportedVideoException::class);

        $this->renderer()->render(new UrlProductVideo());
    }

    private function renderer(): CloudflareStreamProductVideoRenderer
    {
        $twig = new Environment(new ArrayLoader(['renderer' => '{{ player_url }}|{{ hls_url }}|{{ dash_url }}|{{ video.uid }}']));

        return new CloudflareStreamProductVideoRenderer($twig, new CloudflareStreamUrlGenerator('abc'), 'renderer');
    }
}
