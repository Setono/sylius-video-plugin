<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;

final class UrlProductVideoTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_url_discriminator_type(): void
    {
        self::assertSame('url', UrlProductVideo::getType());
    }

    /**
     * @test
     */
    public function it_holds_a_url(): void
    {
        $video = new UrlProductVideo();

        self::assertNull($video->getUrl());

        $video->setUrl('https://example.com/video');

        self::assertSame('https://example.com/video', $video->getUrl());
    }
}
