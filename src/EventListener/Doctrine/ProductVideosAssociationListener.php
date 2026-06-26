<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Setono\SyliusVideoPlugin\Model\ProductVideosAwareInterface;

/**
 * Maps the inverse `videos` OneToMany association onto any entity implementing
 * {@see ProductVideosAwareInterface} (i.e. the application's Product). This is why adopting an
 * application only needs to implement the interface and use {@see \Setono\SyliusVideoPlugin\Model\ProductVideosAwareTrait}
 * on its Product — the association is wired here instead of in hand-written ORM mapping.
 */
final class ProductVideosAssociationListener
{
    /**
     * @param class-string $productVideoClass
     */
    public function __construct(
        private readonly string $productVideoClass,
    ) {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        /** @var ClassMetadata<object> $metadata */
        $metadata = $eventArgs->getClassMetadata();

        if (!is_a($metadata->getName(), ProductVideosAwareInterface::class, true)) {
            return;
        }

        if ($metadata->hasAssociation('videos')) {
            return;
        }

        $metadata->mapOneToMany([
            'fieldName' => 'videos',
            'targetEntity' => $this->productVideoClass,
            'mappedBy' => 'product',
            'cascade' => ['persist'],
            'orphanRemoval' => true,
            'orderBy' => ['position' => 'ASC'],
        ]);
    }
}
