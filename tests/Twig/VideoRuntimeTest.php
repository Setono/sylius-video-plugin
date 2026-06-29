<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Poster\VideoPosterResolverInterface;
use Setono\SyliusVideoPlugin\Renderer\VideoRendererInterface;
use Setono\SyliusVideoPlugin\Twig\VideoRuntime;

final class VideoRuntimeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_delegates_rendering_to_the_renderer(): void
    {
        $video = new UrlProductVideo();

        $renderer = $this->prophesize(VideoRendererInterface::class);
        $renderer->render($video, ['foo' => 'bar'])->willReturn('<video></video>');

        $runtime = new VideoRuntime($renderer->reveal(), $this->prophesize(VideoPosterResolverInterface::class)->reveal());

        self::assertSame('<video></video>', $runtime->render($video, ['foo' => 'bar']));
    }

    /**
     * @test
     */
    public function it_delegates_poster_resolution_to_the_resolver(): void
    {
        $video = new UrlProductVideo();

        $resolver = $this->prophesize(VideoPosterResolverInterface::class);
        $resolver->resolve($video)->willReturn('https://example.com/poster.jpg');

        $runtime = new VideoRuntime($this->prophesize(VideoRendererInterface::class)->reveal(), $resolver->reveal());

        self::assertSame('https://example.com/poster.jpg', $runtime->poster($video));
    }
}
