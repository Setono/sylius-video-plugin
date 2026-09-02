<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;

final class EmbedProductVideoTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_embed_discriminator_type(): void
    {
        self::assertSame('embed', EmbedProductVideo::getType());
    }

    /**
     * @test
     */
    public function it_holds_embed_html(): void
    {
        $video = new EmbedProductVideo();

        self::assertNull($video->getHtml());

        $video->setHtml('<iframe></iframe>');

        self::assertSame('<iframe></iframe>', $video->getHtml());
    }
}
