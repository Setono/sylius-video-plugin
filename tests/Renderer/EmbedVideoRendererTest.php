<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\EmbedVideo;
use Setono\SyliusVideoPlugin\Model\UrlVideo;
use Setono\SyliusVideoPlugin\Renderer\EmbedVideoRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class EmbedVideoRendererTest extends TestCase
{
    /**
     * @test
     */
    public function it_supports_only_embed_videos(): void
    {
        self::assertTrue($this->renderer()->supports(new EmbedVideo()));
        self::assertFalse($this->renderer()->supports(new UrlVideo()));
    }

    /**
     * @test
     */
    public function it_renders_the_embed_html(): void
    {
        $twig = new Environment(new ArrayLoader(['renderer' => '{{ html|raw }}']));

        $video = new EmbedVideo();
        $video->setHtml('<iframe></iframe>');

        $renderer = new EmbedVideoRenderer($twig, 'renderer');

        self::assertSame('<iframe></iframe>', $renderer->render($video));
    }

    private function renderer(): EmbedVideoRenderer
    {
        return new EmbedVideoRenderer(new Environment(new ArrayLoader()), 'renderer');
    }
}
