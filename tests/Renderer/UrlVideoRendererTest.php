<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\EmbedVideo;
use Setono\SyliusVideoPlugin\Model\UrlVideo;
use Setono\SyliusVideoPlugin\Renderer\UrlVideoRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class UrlVideoRendererTest extends TestCase
{
    /**
     * @test
     */
    public function it_supports_only_url_videos(): void
    {
        $renderer = $this->renderer();

        self::assertTrue($renderer->supports(new UrlVideo()));
        self::assertFalse($renderer->supports(new EmbedVideo()));
    }

    /**
     * @test
     */
    public function it_flags_direct_video_files(): void
    {
        $video = new UrlVideo();
        $video->setUrl('https://example.com/clip.mp4');

        self::assertSame('direct', $this->renderer()->render($video));
    }

    /**
     * @test
     */
    public function it_treats_platform_urls_as_embeds(): void
    {
        $video = new UrlVideo();
        $video->setUrl('https://www.youtube.com/watch?v=abc');

        self::assertSame('iframe', $this->renderer()->render($video));
    }

    private function renderer(): UrlVideoRenderer
    {
        $twig = new Environment(new ArrayLoader([
            'renderer' => '{{ is_direct_file ? "direct" : "iframe" }}',
        ]));

        return new UrlVideoRenderer($twig, 'renderer');
    }
}
