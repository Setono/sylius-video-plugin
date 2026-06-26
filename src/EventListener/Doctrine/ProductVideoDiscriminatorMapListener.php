<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Setono\SyliusVideoPlugin\Model\EmbedVideoInterface;
use Setono\SyliusVideoPlugin\Model\FileVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\UrlVideoInterface;

/**
 * Populates the Single Table Inheritance discriminator map on the base ProductVideo entity at
 * the moment Doctrine loads its metadata. The map keys are the TYPE_* discriminator values used
 * throughout the plugin; they are stable across app-level subclassing because they are derived
 * from the plugin's own subtype interfaces, not from the (overridable) concrete class names.
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

            $key = $this->getDiscriminatorKey($model);

            if (null === $key) {
                continue;
            }

            $map[$key] = $model;
        }

        return $map;
    }

    /**
     * @param class-string $class
     */
    private function getDiscriminatorKey(string $class): ?string
    {
        // The three subtype interfaces explicitly map to the discriminator values used
        // throughout the plugin (form field value, spec language). Anything that is not one of
        // the known subtypes is skipped — the base ProductVideo class itself is not in the map.
        if (is_a($class, FileVideoInterface::class, true)) {
            return ProductVideoInterface::TYPE_FILE;
        }

        if (is_a($class, UrlVideoInterface::class, true)) {
            return ProductVideoInterface::TYPE_URL;
        }

        if (is_a($class, EmbedVideoInterface::class, true)) {
            return ProductVideoInterface::TYPE_EMBED;
        }

        return null;
    }
}
