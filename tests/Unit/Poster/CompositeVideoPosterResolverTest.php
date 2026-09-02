<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Poster;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Poster\CompositeVideoPosterResolver;
use Setono\SyliusVideoPlugin\Poster\VideoPosterResolverInterface;

final class CompositeVideoPosterResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_returns_the_first_supporting_resolvers_url(): void
    {
        $video = new UrlProductVideo();

        $first = $this->prophesize(VideoPosterResolverInterface::class);
        $first->supports($video)->willReturn(false);
        $first->resolve($video)->shouldNotBeCalled();

        $second = $this->prophesize(VideoPosterResolverInterface::class);
        $second->supports($video)->willReturn(true);
        $second->resolve($video)->willReturn('https://example.com/poster.jpg');

        $composite = new CompositeVideoPosterResolver();
        $composite->add($first->reveal());
        $composite->add($second->reveal());

        self::assertTrue($composite->supports($video));
        self::assertSame('https://example.com/poster.jpg', $composite->resolve($video));
    }

    /**
     * @test
     */
    public function it_returns_null_when_no_resolver_supports_the_video(): void
    {
        $video = new UrlProductVideo();

        $resolver = $this->prophesize(VideoPosterResolverInterface::class);
        $resolver->supports($video)->willReturn(false);

        $composite = new CompositeVideoPosterResolver();
        $composite->add($resolver->reveal());

        self::assertFalse($composite->supports($video));
        self::assertNull($composite->resolve($video));
    }
}
