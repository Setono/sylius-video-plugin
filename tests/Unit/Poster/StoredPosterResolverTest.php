<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Poster;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Filesystem\MediaUrlGeneratorInterface;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Poster\StoredPosterResolver;

final class StoredPosterResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_supports_only_videos_with_a_stored_poster(): void
    {
        $resolver = new StoredPosterResolver($this->prophesize(MediaUrlGeneratorInterface::class)->reveal());

        $without = new EmbedProductVideo();
        $with = new EmbedProductVideo();
        $with->setPosterPath('video/poster/ab/cd.jpg');

        self::assertFalse($resolver->supports($without));
        self::assertTrue($resolver->supports($with));
    }

    /**
     * @test
     */
    public function it_resolves_the_stored_poster_to_a_public_url(): void
    {
        $generator = $this->prophesize(MediaUrlGeneratorInterface::class);
        $generator->generate('video/poster/ab/cd.jpg')->willReturn('/media/image/video/poster/ab/cd.jpg');

        $resolver = new StoredPosterResolver($generator->reveal());

        $video = new EmbedProductVideo();
        $video->setPosterPath('video/poster/ab/cd.jpg');

        self::assertSame('/media/image/video/poster/ab/cd.jpg', $resolver->resolve($video));
    }

    /**
     * @test
     */
    public function it_resolves_to_null_without_a_stored_poster(): void
    {
        $resolver = new StoredPosterResolver($this->prophesize(MediaUrlGeneratorInterface::class)->reveal());

        self::assertNull($resolver->resolve(new EmbedProductVideo()));
    }
}
