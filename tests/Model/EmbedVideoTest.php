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
        self::assertSame(ProductVideoInterface::TYPE_EMBED, (new EmbedVideo())->getType());
    }

    /**
     * @test
     */
    public function it_holds_embed_code(): void
    {
        $video = new EmbedVideo();

        self::assertNull($video->getCode());

        $video->setCode('<iframe></iframe>');

        self::assertSame('<iframe></iframe>', $video->getCode());
    }
}
