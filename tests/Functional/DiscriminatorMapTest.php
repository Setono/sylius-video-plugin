<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;

final class DiscriminatorMapTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_resolves_the_single_table_inheritance_map_from_the_registered_resources(): void
    {
        $metadata = $this->service(EntityManagerInterface::class)->getClassMetadata(ProductVideo::class);

        self::assertSame([
            'file' => FileProductVideo::class,
            'url' => UrlProductVideo::class,
            'embed' => EmbedProductVideo::class,
        ], $metadata->discriminatorMap);
        self::assertSame('type', $metadata->discriminatorColumn['name'] ?? null);
        self::assertSame('setono_sylius_video__product_video', $metadata->getTableName());
    }

    /**
     * @test
     */
    public function it_gives_each_subtype_its_own_discriminator_value(): void
    {
        $manager = $this->service(EntityManagerInterface::class);

        foreach (['file' => FileProductVideo::class, 'url' => UrlProductVideo::class, 'embed' => EmbedProductVideo::class] as $type => $class) {
            self::assertSame($type, $manager->getClassMetadata($class)->discriminatorValue);
        }
    }
}
