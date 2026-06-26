<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Model\EmbedVideo;
use Setono\SyliusVideoPlugin\Model\UrlVideo;
use Setono\SyliusVideoPlugin\Renderer\EmbedVideoRenderer;
use Setono\SyliusVideoPlugin\Sanitizer\EmbedSanitizerInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class EmbedVideoRendererTest extends TestCase
{
    use ProphecyTrait;

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
    public function it_renders_the_sanitized_embed_code(): void
    {
        $sanitizer = $this->prophesize(EmbedSanitizerInterface::class);
        $sanitizer->sanitize('<iframe></iframe>')->willReturn('<iframe data-safe></iframe>');

        $twig = new Environment(new ArrayLoader(['renderer' => '{{ code|raw }}']));

        $video = new EmbedVideo();
        $video->setCode('<iframe></iframe>');

        $renderer = new EmbedVideoRenderer($twig, $sanitizer->reveal(), 'renderer');

        self::assertSame('<iframe data-safe></iframe>', $renderer->render($video));
    }

    private function renderer(): EmbedVideoRenderer
    {
        return new EmbedVideoRenderer(
            new Environment(new ArrayLoader()),
            $this->prophesize(EmbedSanitizerInterface::class)->reveal(),
            'renderer',
        );
    }
}
