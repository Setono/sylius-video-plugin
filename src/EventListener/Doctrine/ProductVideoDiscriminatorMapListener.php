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

        $map = $this->buildDiscriminatorMap();

        // By the time this event fires Doctrine has already filled in its default map: the abstract
        // root and every mapped subclass under a lower-cased short class name. Those entries are
        // ours to replace, and since setDiscriminatorMap() only appends they have to go first, or
        // every subtype would keep two discriminator values. An entry for a class this plugin does
        // not know was added by another listener on purpose and is kept; it may not, however, claim
        // one of our discriminator values.
        foreach ($metadata->discriminatorMap as $value => $class) {
            if ($class === $metadata->getName() || in_array($class, $map, true)) {
                unset($metadata->discriminatorMap[$value]);

                continue;
            }

            if (isset($map[$value])) {
                throw new \LogicException(sprintf(
                    'Cannot map video type "%s" to "%s": the discriminator value is already mapped to "%s" by another listener.',
                    $value,
                    $map[$value],
                    $class,
                ));
            }
        }

        $metadata->setDiscriminatorMap($map);
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
