<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
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
        $renderer->render($video)->willReturn('<video></video>');

        $runtime = new VideoRuntime($renderer->reveal(), $this->prophesize(VideoPosterResolverInterface::class)->reveal());

        self::assertSame('<video></video>', $runtime->render($video));
    }

    /**
     * @test
     */
    public function it_renders_nothing_and_logs_when_no_renderer_supports_the_video(): void
    {
        $video = new UrlProductVideo();

        $renderer = $this->prophesize(VideoRendererInterface::class);
        $renderer->render($video)->willThrow(new UnsupportedVideoException($video));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->warning(Argument::containingString('no renderer supports its type'), Argument::withEntry('type', 'url'))->shouldBeCalledOnce();

        $runtime = new VideoRuntime($renderer->reveal(), $this->prophesize(VideoPosterResolverInterface::class)->reveal(), $logger->reveal());

        self::assertSame('', $runtime->render($video));
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
