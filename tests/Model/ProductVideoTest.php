<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Sylius\Component\Core\Model\ProductInterface;

final class ProductVideoTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_throws_when_resolving_the_type_on_the_base_class(): void
    {
        $this->expectException(\LogicException::class);

        ProductVideo::getType();
    }

    /**
     * @test
     */
    public function it_holds_a_product(): void
    {
        $video = new ProductVideo();
        $product = $this->prophesize(ProductInterface::class)->reveal();

        $video->setProduct($product);

        self::assertSame($product, $video->getProduct());
    }

    /**
     * @test
     */
    public function it_holds_a_position(): void
    {
        $video = new ProductVideo();

        self::assertNull($video->getPosition());

        $video->setPosition(3);

        self::assertSame(3, $video->getPosition());
    }

    /**
     * @test
     */
    public function it_holds_a_stored_poster_path(): void
    {
        $video = new ProductVideo();

        self::assertNull($video->getPosterPath());

        $video->setPosterPath('video/poster/a/b.jpg');

        self::assertSame('video/poster/a/b.jpg', $video->getPosterPath());
    }

    /**
     * @test
     */
    public function it_carries_a_pending_poster_file(): void
    {
        $video = new ProductVideo();

        self::assertFalse($video->hasPosterFile());
        self::assertNull($video->getPosterFile());

        $file = new \SplFileInfo(__FILE__);
        $video->setPosterFile($file);

        self::assertTrue($video->hasPosterFile());
        self::assertSame($file, $video->getPosterFile());
    }

    /**
     * @test
     */
    public function it_exposes_timestamps(): void
    {
        $video = new ProductVideo();
        $createdAt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $updatedAt = new \DateTimeImmutable('2026-01-02 00:00:00');

        $video->setCreatedAt($createdAt);
        $video->setUpdatedAt($updatedAt);

        self::assertSame($createdAt, $video->getCreatedAt());
        self::assertSame($updatedAt, $video->getUpdatedAt());
        self::assertNull($video->getId());
    }
}
