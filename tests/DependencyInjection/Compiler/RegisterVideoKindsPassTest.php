<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\DependencyInjection\Compiler\RegisterVideoKindsPass;
use Setono\SyliusVideoPlugin\Kind\VideoKindRegistry;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterVideoKindsPassTest extends TestCase
{
    /**
     * @test
     */
    public function it_builds_the_registry_from_resources_carrying_the_attribute(): void
    {
        $container = $this->containerWithResources([
            // The base (no #[AsVideoKind]) and unrelated resources must be skipped without error.
            'setono_sylius_video.product_video' => ProductVideo::class,
            'app.unrelated' => \stdClass::class,
            'setono_sylius_video.file_video' => FileProductVideo::class,
            'setono_sylius_video.url_video' => UrlProductVideo::class,
            'setono_sylius_video.embed_video' => EmbedProductVideo::class,
        ]);

        (new RegisterVideoKindsPass())->process($container);

        /** @var array<array-key, array{type: string, label: string, field: string, model: class-string, factory: Reference}> $kinds */
        $kinds = $container->getDefinition(VideoKindRegistry::class)->getArgument(0);

        $byType = [];
        foreach ($kinds as $kind) {
            $byType[$kind['type']] = $kind;
        }

        self::assertSame(['file', 'url', 'embed'], array_keys($byType));

        self::assertSame('setono_sylius_video.type.file', $byType['file']['label']);
        self::assertSame('file', $byType['file']['field']);
        self::assertSame(FileProductVideo::class, $byType['file']['model']);
        self::assertSame('setono_sylius_video.factory.file_video', (string) $byType['file']['factory']);

        self::assertSame('url', $byType['url']['field']);
        self::assertSame('html', $byType['embed']['field']);
        self::assertSame('setono_sylius_video.factory.embed_video', (string) $byType['embed']['factory']);
    }

    /**
     * @test
     */
    public function it_skips_a_kind_whose_factory_service_is_missing(): void
    {
        $container = $this->containerWithResources([
            'setono_sylius_video.file_video' => FileProductVideo::class,
        ]);
        $container->removeDefinition('setono_sylius_video.factory.file_video');

        (new RegisterVideoKindsPass())->process($container);

        self::assertSame([], $container->getDefinition(VideoKindRegistry::class)->getArgument(0));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_registry_is_not_registered(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('sylius.resources', []);

        (new RegisterVideoKindsPass())->process($container);

        self::assertFalse($container->hasDefinition(VideoKindRegistry::class));
    }

    /**
     * @param array<string, class-string> $models keyed by resource alias
     */
    private function containerWithResources(array $models): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register(VideoKindRegistry::class, VideoKindRegistry::class)->setArgument(0, []);

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
