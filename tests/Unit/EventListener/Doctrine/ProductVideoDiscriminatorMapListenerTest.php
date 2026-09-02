<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\ProductVideoDiscriminatorMapListener;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Tests\Unit\EventListener\Doctrine\Fixtures\CustomFileProductVideo;

final class ProductVideoDiscriminatorMapListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_builds_the_discriminator_map_on_the_base_class_keyed_by_type(): void
    {
        $metadata = new ClassMetadata(ProductVideo::class);
        // Doctrine's default map (short class names, including the abstract base) is present by
        // the time the listener runs and must be replaced, not merged.
        $metadata->setDiscriminatorMap([
            'productvideo' => ProductVideo::class,
            'fileproductvideo' => FileProductVideo::class,
        ]);

        $this->listener()->loadClassMetadata($this->eventArgs($metadata));

        self::assertSame([
            'file' => FileProductVideo::class,
            'url' => UrlProductVideo::class,
            'embed' => EmbedProductVideo::class,
        ], $metadata->discriminatorMap);
        self::assertSame([FileProductVideo::class, UrlProductVideo::class, EmbedProductVideo::class], $metadata->subClasses);
    }

    /**
     * @test
     */
    public function it_keeps_entries_other_listeners_added_for_classes_it_does_not_know(): void
    {
        $metadata = new ClassMetadata(ProductVideo::class);
        $metadata->setDiscriminatorMap([
            'productvideo' => ProductVideo::class,
            'fileproductvideo' => FileProductVideo::class,
            // Registered by some other listener, not as a Sylius resource.
            'custom' => CustomFileProductVideo::class,
        ]);

        $this->listener()->loadClassMetadata($this->eventArgs($metadata));

        self::assertSame([
            'custom' => CustomFileProductVideo::class,
            'file' => FileProductVideo::class,
            'url' => UrlProductVideo::class,
            'embed' => EmbedProductVideo::class,
        ], $metadata->discriminatorMap);
        self::assertContains(CustomFileProductVideo::class, $metadata->subClasses);
    }

    /**
     * @test
     */
    public function it_throws_when_another_listener_already_uses_one_of_its_discriminator_values(): void
    {
        $metadata = new ClassMetadata(ProductVideo::class);
        $metadata->setDiscriminatorMap(['url' => CustomFileProductVideo::class]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"url"');

        $this->listener()->loadClassMetadata($this->eventArgs($metadata));
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

    /**
     * @test
     */
    public function it_throws_when_two_models_resolve_to_the_same_type(): void
    {
        $listener = new ProductVideoDiscriminatorMapListener([
            'file_video' => ['classes' => ['model' => FileProductVideo::class]],
            'custom_file_video' => ['classes' => ['model' => CustomFileProductVideo::class]],
        ]);

        $this->expectException(\LogicException::class);

        $listener->loadClassMetadata($this->eventArgs(new ClassMetadata(ProductVideo::class)));
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
