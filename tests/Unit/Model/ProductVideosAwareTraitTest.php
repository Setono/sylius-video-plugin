<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\ProductVideosAwareInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideosAwareTrait;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Sylius\Component\Core\Model\Product;

final class ProductVideosAwareTraitTest extends TestCase
{
    /**
     * @test
     */
    public function it_adds_a_video_and_sets_its_product(): void
    {
        $product = new ProductWithVideos();
        $video = new UrlProductVideo();

        $product->addVideo($video);

        self::assertTrue($product->hasVideo($video));
        self::assertSame($product, $video->getProduct());
    }

    /**
     * @test
     */
    public function it_does_not_add_the_same_video_twice(): void
    {
        $product = new ProductWithVideos();
        $video = new UrlProductVideo();

        $product->addVideo($video);
        $product->addVideo($video);

        self::assertCount(1, $product->getVideos());
    }

    /**
     * @test
     */
    public function it_removes_a_video_and_unsets_its_product(): void
    {
        $product = new ProductWithVideos();
        $video = new UrlProductVideo();
        $product->addVideo($video);

        $product->removeVideo($video);

        self::assertFalse($product->hasVideo($video));
        self::assertNull($video->getProduct());
    }

    /**
     * @test
     */
    public function it_exposes_all_added_videos_in_position_order(): void
    {
        $product = new ProductWithVideos();

        $first = new UrlProductVideo();
        $second = new UrlProductVideo();

        $product->addVideo($first);
        $product->addVideo($second);

        self::assertTrue($product->hasVideos());
        self::assertCount(2, $product->getVideos());
        self::assertTrue($product->getVideos()->contains($first));
        self::assertTrue($product->getVideos()->contains($second));
    }
}

/**
 * Fixture: the minimal Product an adopting application would create — base Sylius product +
 * the aware interface and trait.
 */
final class ProductWithVideos extends Product implements ProductVideosAwareInterface
{
    use ProductVideosAwareTrait;
}
