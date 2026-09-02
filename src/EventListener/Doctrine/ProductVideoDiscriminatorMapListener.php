<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Type\ProductVideoTypes;

/**
 * Populates the Single Table Inheritance discriminator map on the base ProductVideo entity at
 * the moment Doctrine loads its metadata. The discriminator value for each subtype is resolved
 * dynamically from the model itself ({@see ProductVideoInterface::getType()}), so the map keys
 * are stable across app-level subclassing and adding a type never requires editing this listener.
 *
 * The plugin's ORM XML declares `inheritance-type="SINGLE_TABLE"` and the discriminator column
 * on ProductVideo, but omits the map because the concrete subtype classes may be overridden or
 * extended by the adopting application. The types come from {@see ProductVideoTypes}, which scans
 * *all* registered Sylius resources (`%sylius.resources%`) and keeps the ones whose model implements
 * ProductVideoInterface, so a new video type registered as a plain resource is picked up without
 * any plugin configuration.
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

        foreach (ProductVideoTypes::fromResources($this->resources) as $type => ['model' => $model]) {
            $map[$type] = $model;
        }

        return $map;
    }
}
