<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\DependencyInjection\Compiler;

use Setono\SyliusVideoPlugin\Kind\AsVideoKind;
use Setono\SyliusVideoPlugin\Kind\VideoKindRegistry;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Builds the {@see VideoKindRegistry} from every registered Sylius resource whose model
 * implements {@see ProductVideoInterface} and carries an {@see AsVideoKind} attribute. The kind
 * metadata therefore lives next to the entity it describes — adding a kind is "subtype +
 * #[AsVideoKind] + a resource entry", with no plugin configuration to edit.
 */
final class RegisterVideoKindsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(VideoKindRegistry::class) || !$container->hasParameter('sylius.resources')) {
            return;
        }

        /** @var array<array-key, array{classes?: array{model?: class-string}}> $resources */
        $resources = (array) $container->getParameter('sylius.resources');

        $kinds = [];

        foreach ($resources as $alias => $resource) {
            if (!is_string($alias)) {
                continue;
            }

            $model = $resource['classes']['model'] ?? null;

            if (!is_string($model) || !is_a($model, ProductVideoInterface::class, true)) {
                continue;
            }

            $attributes = (new \ReflectionClass($model))->getAttributes(AsVideoKind::class);

            if ([] === $attributes) {
                continue;
            }

            $factoryId = $this->factoryId($alias);

            if (null === $factoryId || !$container->has($factoryId)) {
                continue;
            }

            $kind = $attributes[0]->newInstance();

            $kinds[] = [
                // Single source of truth for the discriminator value (see the model's getType()).
                'type' => $model::getType(),
                'label' => $kind->label,
                'field' => $kind->field,
                'model' => $model,
                'factory' => new Reference($factoryId),
            ];
        }

        $container->getDefinition(VideoKindRegistry::class)->setArgument(0, $kinds);
    }

    /**
     * The Sylius factory service for a resource alias `<app>.<name>` is `<app>.factory.<name>`.
     */
    private function factoryId(string $alias): ?string
    {
        $parts = explode('.', $alias, 2);

        if (2 !== \count($parts)) {
            return null;
        }

        return sprintf('%s.factory.%s', $parts[0], $parts[1]);
    }
}
