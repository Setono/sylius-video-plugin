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
 * are stable across app-level subclassing and adding a type never requires editing this listener.
 *
 * The plugin's ORM XML declares `inheritance-type="SINGLE_TABLE"` and the discriminator column
 * on ProductVideo, but omits the map because the concrete subtype classes may be overridden or
 * extended by the adopting application. The listener scans *all* registered Sylius resources
 * (`%sylius.resources%`) and keeps the ones whose model implements ProductVideoInterface, so a
 * new video type registered as a plain resource is picked up without any plugin configuration.
 */
final class ProductVideoDiscriminatorMapListener
{
    /**
     * @param array<array-key, array{classes?: array{model?: class-string}}> $resources
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
                $type = $model::getType();
            } catch (\Throwable) {
                continue;
            }

            // Two models claiming the same discriminator (e.g. a subtype extending a concrete one
            // without overriding getType()) would silently shadow each other in the STI map and
            // break hydration — fail loudly instead.
            if (isset($map[$type]) && $map[$type] !== $model) {
                throw new \LogicException(sprintf(
                    'Video types must be unique, but "%s" and "%s" both resolve to "%s". Override getType() on one of them.',
                    $map[$type],
                    $model,
                    $type,
                ));
            }

            $map[$type] = $model;
        }

        return $map;
    }
}
