<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Renderer\UrlProductVideoRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class UrlProductVideoRendererTest extends TestCase
{
    /**
     * @test
     */
    public function it_supports_only_url_videos(): void
    {
        $renderer = $this->renderer();

        self::assertTrue($renderer->supports(new UrlProductVideo()));
        self::assertFalse($renderer->supports(new EmbedProductVideo()));
    }

    /**
     * @test
     */
    public function it_flags_direct_video_files(): void
    {
        $video = new UrlProductVideo();
        $video->setUrl('https://example.com/clip.mp4');

        self::assertSame('direct', $this->renderer()->render($video));
    }

    /**
     * @test
     */
    public function it_treats_platform_urls_as_embeds(): void
    {
        $video = new UrlProductVideo();
        $video->setUrl('https://www.youtube.com/watch?v=abc');

        self::assertSame('iframe', $this->renderer()->render($video));
    }

    private function renderer(): UrlProductVideoRenderer
    {
        $twig = new Environment(new ArrayLoader([
            'renderer' => '{{ is_direct_file ? "direct" : "iframe" }}',
        ]));

        return new UrlProductVideoRenderer($twig, 'renderer');
    }
}
