<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Model\UrlVideo;
use Setono\SyliusVideoPlugin\Renderer\CompositeVideoRenderer;
use Setono\SyliusVideoPlugin\Renderer\VideoRendererInterface;

final class CompositeVideoRendererTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_delegates_to_the_first_supporting_renderer(): void
    {
        $video = new UrlVideo();

        $first = $this->prophesize(VideoRendererInterface::class);
        $first->supports($video)->willReturn(false);
        $first->render($video, [])->shouldNotBeCalled();

        $second = $this->prophesize(VideoRendererInterface::class);
        $second->supports($video)->willReturn(true);
        $second->render($video, [])->willReturn('<video></video>');

        $third = $this->prophesize(VideoRendererInterface::class);
        $third->supports($video)->shouldNotBeCalled();

        $composite = new CompositeVideoRenderer();
        $composite->add($first->reveal());
        $composite->add($second->reveal());
        $composite->add($third->reveal());

        self::assertTrue($composite->supports($video));
        self::assertSame('<video></video>', $composite->render($video));
    }

    /**
     * @test
     */
    public function it_throws_when_no_renderer_supports_the_video(): void
    {
        $video = new UrlVideo();

        $renderer = $this->prophesize(VideoRendererInterface::class);
        $renderer->supports($video)->willReturn(false);

        $composite = new CompositeVideoRenderer();
        $composite->add($renderer->reveal());

        self::assertFalse($composite->supports($video));

        $this->expectException(UnsupportedVideoException::class);

        $composite->render($video);
    }
}
