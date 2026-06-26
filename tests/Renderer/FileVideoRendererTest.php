<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Renderer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Filesystem\MediaUrlGeneratorInterface;
use Setono\SyliusVideoPlugin\Model\FileVideo;
use Setono\SyliusVideoPlugin\Model\UrlVideo;
use Setono\SyliusVideoPlugin\Renderer\FileVideoRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class FileVideoRendererTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_supports_only_file_videos(): void
    {
        self::assertTrue($this->renderer(new Environment(new ArrayLoader()))->supports(new FileVideo()));
        self::assertFalse($this->renderer(new Environment(new ArrayLoader()))->supports(new UrlVideo()));
    }

    /**
     * @test
     */
    public function it_renders_the_public_url_of_the_stored_file(): void
    {
        $generator = $this->prophesize(MediaUrlGeneratorInterface::class);
        $generator->generate('video/ab/cd.mp4')->willReturn('/media/image/video/ab/cd.mp4');

        $twig = new Environment(new ArrayLoader(['renderer' => '{{ url }}']));

        $video = new FileVideo();
        $video->setPath('video/ab/cd.mp4');

        $renderer = new FileVideoRenderer($twig, $generator->reveal(), 'renderer');

        self::assertSame('/media/image/video/ab/cd.mp4', $renderer->render($video));
    }

    /**
     * @test
     */
    public function it_throws_when_asked_to_render_an_unsupported_video(): void
    {
        $this->expectException(\Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException::class);

        $this->renderer(new Environment(new ArrayLoader(['renderer' => ''])))->render(new UrlVideo());
    }

    private function renderer(Environment $twig): FileVideoRenderer
    {
        return new FileVideoRenderer($twig, $this->prophesize(MediaUrlGeneratorInterface::class)->reveal(), 'renderer');
    }
}
