<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\DependencyInjection\Compiler;

use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Type\VideoTypeRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Builds the {@see VideoTypeRegistry} from every registered Sylius resource whose model implements
 * {@see ProductVideoInterface}. The discriminator type comes from the model's getType() and the
 * choice label is derived from it, so adding a type is just "register a resource" — the input
 * fields are contributed by a per-type ProductVideoType extension.
 */
final class RegisterVideoTypesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(VideoTypeRegistry::class) || !$container->hasParameter('sylius.resources')) {
            return;
        }

        /** @var array<array-key, array{classes?: array{model?: class-string}}> $resources */
        $resources = (array) $container->getParameter('sylius.resources');

        $types = [];

        foreach ($resources as $alias => $resource) {
            if (!is_string($alias)) {
                continue;
            }

            $model = $resource['classes']['model'] ?? null;

            if (!is_string($model) || !is_a($model, ProductVideoInterface::class, true)) {
                continue;
            }

            try {
                // The base ProductVideo has no discriminator type and throws; concrete subtypes
                // derive one from their class name.
                $type = $model::getType();
            } catch (\Throwable) {
                continue;
            }

            $factoryId = $this->factoryId($alias);

            if (null === $factoryId || !$container->has($factoryId)) {
                continue;
            }

            $types[] = [
                'type' => $type,
                'label' => sprintf('setono_sylius_video.ui.types.%s', $type),
                'factory' => new Reference($factoryId),
            ];
        }

        $container->getDefinition(VideoTypeRegistry::class)->setArgument(0, $types);
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
