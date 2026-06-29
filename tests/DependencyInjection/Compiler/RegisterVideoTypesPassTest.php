<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\DependencyInjection\Compiler\RegisterVideoTypesPass;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Type\VideoTypeRegistry;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterVideoTypesPassTest extends TestCase
{
    /**
     * @test
     */
    public function it_builds_the_registry_from_resources_implementing_the_interface(): void
    {
        $container = $this->containerWithResources([
            // The base (getType() throws) and unrelated resources must be skipped without error.
            'setono_sylius_video.product_video' => ProductVideo::class,
            'app.unrelated' => \stdClass::class,
            'setono_sylius_video.file_video' => FileProductVideo::class,
            'setono_sylius_video.url_video' => UrlProductVideo::class,
            'setono_sylius_video.embed_video' => EmbedProductVideo::class,
        ]);

        (new RegisterVideoTypesPass())->process($container);

        /** @var array<array-key, array{type: string, label: string, factory: Reference}> $types */
        $types = $container->getDefinition(VideoTypeRegistry::class)->getArgument(0);

        $byType = [];
        foreach ($types as $type) {
            $byType[$type['type']] = $type;
        }

        self::assertSame(['file', 'url', 'embed'], array_keys($byType));

        // The label is derived from the type.
        self::assertSame('setono_sylius_video.ui.types.file', $byType['file']['label']);
        self::assertSame('setono_sylius_video.ui.types.embed', $byType['embed']['label']);

        // The factory is resolved from the resource alias.
        self::assertSame('setono_sylius_video.factory.file_video', (string) $byType['file']['factory']);
        self::assertSame('setono_sylius_video.factory.embed_video', (string) $byType['embed']['factory']);
    }

    /**
     * @test
     */
    public function it_skips_a_type_whose_factory_service_is_missing(): void
    {
        $container = $this->containerWithResources([
            'setono_sylius_video.file_video' => FileProductVideo::class,
        ]);
        $container->removeDefinition('setono_sylius_video.factory.file_video');

        (new RegisterVideoTypesPass())->process($container);

        self::assertSame([], $container->getDefinition(VideoTypeRegistry::class)->getArgument(0));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_registry_is_not_registered(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sylius.resources', []);

        (new RegisterVideoTypesPass())->process($container);

        self::assertFalse($container->hasDefinition(VideoTypeRegistry::class));
    }

    /**
     * @param array<string, class-string> $models keyed by resource alias
     */
    private function containerWithResources(array $models): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register(VideoTypeRegistry::class, VideoTypeRegistry::class)->setArgument(0, []);

        $resources = [];
        foreach ($models as $alias => $model) {
            $resources[$alias] = ['classes' => ['model' => $model]];

            // Register the factory service the pass references (id: <app>.factory.<name>).
            [$app, $name] = explode('.', $alias, 2);
            $container->register(sprintf('%s.factory.%s', $app, $name), Factory::class);
        }

        $container->setParameter('sylius.resources', $resources);

        return $container;
    }
}
