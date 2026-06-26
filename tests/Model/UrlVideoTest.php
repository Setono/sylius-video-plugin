<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\UrlVideo;

final class UrlVideoTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_url_discriminator_type(): void
    {
        self::assertSame(ProductVideoInterface::TYPE_URL, (new UrlVideo())->getType());
    }

    /**
     * @test
     */
    public function it_holds_a_url(): void
    {
        $video = new UrlVideo();

        self::assertNull($video->getUrl());

        $video->setUrl('https://example.com/video');

        self::assertSame('https://example.com/video', $video->getUrl());
    }
}
