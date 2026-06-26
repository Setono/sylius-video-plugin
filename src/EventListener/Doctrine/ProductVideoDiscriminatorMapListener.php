<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

/**
 * Populates the Single Table Inheritance discriminator map on the base ProductVideo entity at
 * the moment Doctrine loads its metadata. The discriminator value for each subtype is resolved
 * dynamically from the model itself ({@see ProductVideoInterface::getType()}), so the map keys
 * are stable across app-level subclassing and adding a kind never requires editing this listener.
 *
 * The plugin's ORM XML declares `inheritance-type="SINGLE_TABLE"` and the discriminator column
 * on ProductVideo, but omits the map because the concrete subtype classes may be overridden by
 * the adopting application via `sylius_resource.resources.*.classes.model`. This listener
 * resolves the map from the resource config at runtime.
 */
final class ProductVideoDiscriminatorMapListener
{
    /**
     * @param array<string, array{classes: array{model: class-string}}> $resources
     */
    public function __construct(
        private readonly array $resources,
    ) {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->getName() !== ProductVideo::class) {
            return;
        }

        $metadata->discriminatorMap = $this->buildDiscriminatorMap();
    }

    /**
     * @return array<string, class-string>
     */
    private function buildDiscriminatorMap(): array
    {
        $map = [];

        foreach ($this->resources as $resource) {
            if (!isset($resource['classes']['model'])) {
                continue;
            }

            $model = $resource['classes']['model'];

            if (!is_a($model, ProductVideoInterface::class, true)) {
                continue;
            }

            try {
                // The base class is abstract by convention and throws here; the concrete subtypes
                // return their own discriminator value.
                $type = (new $model())->getType();
            } catch (\Throwable) {
                continue;
            }

            $map[$type] = $model;
        }

        return $map;
    }
}
