<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\EmbedVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

final class EmbedVideoTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_embed_discriminator_type(): void
    {
        self::assertSame(ProductVideoInterface::TYPE_EMBED, EmbedVideo::getType());
    }

    /**
     * @test
     */
    public function it_holds_embed_html(): void
    {
        $video = new EmbedVideo();

        self::assertNull($video->getHtml());

        $video->setHtml('<iframe></iframe>');

        self::assertSame('<iframe></iframe>', $video->getHtml());
    }
}
