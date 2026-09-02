<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Type;

use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

/**
 * Resolves the video types an application has registered as Sylius resources: every resource whose
 * model implements {@see ProductVideoInterface} and derives a discriminator type. Both the compiler
 * pass that builds the type registry and the Doctrine listener that builds the STI map go through
 * here, so the two can never disagree on which types exist.
 */
final class ProductVideoTypes
{
    /**
     * @param array<array-key, array{classes?: array{model?: class-string}}> $resources the `%sylius.resources%` parameter
     *
     * @return array<string, array{alias: string, model: class-string<ProductVideoInterface>}> keyed by type
     *
     * @throws \LogicException when two models resolve to the same type
     */
    public static function fromResources(array $resources): array
    {
        $types = [];

        foreach ($resources as $alias => $resource) {
            $model = $resource['classes']['model'] ?? null;

            if (!is_string($model) || !is_a($model, ProductVideoInterface::class, true)) {
                continue;
            }

            try {
                $type = $model::getType();
            } catch (\LogicException) {
                // The base ProductVideo has no type of its own; only concrete subtypes do.
                continue;
            }

            // Two models claiming the same discriminator (e.g. a subtype extending a concrete one
            // without overriding getType()) would shadow each other in the STI map — fail loudly.
            if (isset($types[$type]) && $types[$type]['model'] !== $model) {
                throw new \LogicException(sprintf(
                    'Video types must be unique, but "%s" and "%s" both resolve to "%s". Override getType() on one of them.',
                    $types[$type]['model'],
                    $model,
                    $type,
                ));
            }

            $types[$type] = ['alias' => (string) $alias, 'model' => $model];
        }

        return $types;
    }
}
