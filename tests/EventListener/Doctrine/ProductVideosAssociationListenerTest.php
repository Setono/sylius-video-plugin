<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\ProductVideosAssociationListener;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Tests\Application\Entity\Product\Product;

final class ProductVideosAssociationListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_maps_the_videos_association_on_a_video_aware_entity(): void
    {
        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn(Product::class);
        $metadata->hasAssociation('videos')->willReturn(false);
        $metadata
            ->mapOneToMany(Argument::that(static function (array $mapping): bool {
                return 'videos' === $mapping['fieldName'] &&
                    ProductVideo::class === $mapping['targetEntity'] &&
                    'product' === $mapping['mappedBy'] &&
                    ['persist'] === $mapping['cascade'] &&
                    true === $mapping['orphanRemoval'] &&
                    ['position' => 'ASC'] === $mapping['orderBy'];
            }))
            ->shouldBeCalledOnce()
        ;

        $this->listener()->loadClassMetadata(new LoadClassMetadataEventArgs($metadata->reveal(), $this->objectManager()));
    }

    /**
     * @test
     */
    public function it_ignores_entities_that_are_not_video_aware(): void
    {
        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn(\stdClass::class);
        $metadata->mapOneToMany(Argument::cetera())->shouldNotBeCalled();

        $this->listener()->loadClassMetadata(new LoadClassMetadataEventArgs($metadata->reveal(), $this->objectManager()));
    }

    /**
     * @test
     */
    public function it_does_not_remap_when_the_association_already_exists(): void
    {
        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn(Product::class);
        $metadata->hasAssociation('videos')->willReturn(true);
        $metadata->mapOneToMany(Argument::cetera())->shouldNotBeCalled();

        $this->listener()->loadClassMetadata(new LoadClassMetadataEventArgs($metadata->reveal(), $this->objectManager()));
    }

    private function listener(): ProductVideosAssociationListener
    {
        return new ProductVideosAssociationListener(ProductVideo::class);
    }

    private function objectManager(): EntityManagerInterface
    {
        return $this->prophesize(EntityManagerInterface::class)->reveal();
    }
}
