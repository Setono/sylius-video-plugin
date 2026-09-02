<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Filesystem\MediaUrlGeneratorInterface;
use Setono\SyliusVideoPlugin\Twig\MediaUrlRuntime;

final class MediaUrlRuntimeTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_delegates_to_the_media_url_generator(): void
    {
        $generator = $this->prophesize(MediaUrlGeneratorInterface::class);
        $generator->generate('video/ab/cd.mp4')->willReturn('/media/image/video/ab/cd.mp4');

        self::assertSame('/media/image/video/ab/cd.mp4', (new MediaUrlRuntime($generator->reveal()))->generate('video/ab/cd.mp4'));
    }
}
