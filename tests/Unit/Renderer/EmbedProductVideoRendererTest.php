<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Renderer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Renderer\EmbedProductVideoRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class EmbedProductVideoRendererTest extends TestCase
{
    /**
     * @test
     */
    public function it_supports_only_embed_videos(): void
    {
        self::assertTrue($this->renderer()->supports(new EmbedProductVideo()));
        self::assertFalse($this->renderer()->supports(new UrlProductVideo()));
    }

    /**
     * @test
     */
    public function it_renders_the_embed_html(): void
    {
        $twig = new Environment(new ArrayLoader(['renderer' => '{{ html|raw }}']));

        $video = new EmbedProductVideo();
        $video->setHtml('<iframe></iframe>');

        $renderer = new EmbedProductVideoRenderer($twig, 'renderer');

        self::assertSame('<iframe></iframe>', $renderer->render($video));
    }

    private function renderer(): EmbedProductVideoRenderer
    {
        return new EmbedProductVideoRenderer(new Environment(new ArrayLoader()), 'renderer');
    }
}
