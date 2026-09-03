<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Tests\Application\Entity\Product;

/**
 * The test application's Product is attribute-mapped and only uses the trait, so this proves the
 * trait's own attributes map the inverse association.
 */
final class ProductMappingTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_maps_the_videos_association_through_the_traits_attributes(): void
    {
        $mapping = $this->service(EntityManagerInterface::class)->getClassMetadata(Product::class)->getAssociationMapping('videos');

        self::assertSame(ProductVideo::class, $mapping['targetEntity']);
        self::assertSame('product', $mapping['mappedBy']);
        self::assertTrue($mapping['orphanRemoval'] ?? false);
        self::assertTrue($mapping['isCascadePersist'] ?? false);
        self::assertSame(['position' => 'ASC'], $mapping['orderBy'] ?? null);
    }
}
