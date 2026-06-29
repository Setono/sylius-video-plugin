<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\ProductVideoDiscriminatorMapListener;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;

final class ProductVideoDiscriminatorMapListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_builds_the_discriminator_map_on_the_base_class_keyed_by_type(): void
    {
        $metadata = new ClassMetadata(ProductVideo::class);

        $this->listener()->loadClassMetadata($this->eventArgs($metadata));

        self::assertSame([
            ProductVideoInterface::TYPE_FILE => FileProductVideo::class,
            ProductVideoInterface::TYPE_URL => UrlProductVideo::class,
            ProductVideoInterface::TYPE_EMBED => EmbedProductVideo::class,
        ], $metadata->discriminatorMap);
    }

    /**
     * @test
     */
    public function it_does_not_touch_metadata_for_other_classes(): void
    {
        $metadata = new ClassMetadata(\stdClass::class);

        $this->listener()->loadClassMetadata($this->eventArgs($metadata));

        self::assertSame([], $metadata->discriminatorMap);
    }

    private function listener(): ProductVideoDiscriminatorMapListener
    {
        return new ProductVideoDiscriminatorMapListener([
            // The base resource and unrelated resources must be skipped.
            'product_video' => ['classes' => ['model' => ProductVideo::class]],
            'unrelated' => ['classes' => ['model' => \stdClass::class]],
            'file_video' => ['classes' => ['model' => FileProductVideo::class]],
            'url_video' => ['classes' => ['model' => UrlProductVideo::class]],
            'embed_video' => ['classes' => ['model' => EmbedProductVideo::class]],
        ]);
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function eventArgs(ClassMetadata $metadata): LoadClassMetadataEventArgs
    {
        return new LoadClassMetadataEventArgs($metadata, $this->prophesize(EntityManagerInterface::class)->reveal());
    }
}
