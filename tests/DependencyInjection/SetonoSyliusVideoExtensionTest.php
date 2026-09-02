<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusVideoPlugin\DependencyInjection\SetonoSyliusVideoExtension;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\ProductVideoDiscriminatorMapListener;
use Setono\SyliusVideoPlugin\Form\Extension\EmbedProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Renderer\CompositeVideoRenderer;
use Setono\SyliusVideoPlugin\Renderer\EmbedProductVideoRenderer;
use Sylius\Component\Core\Filesystem\Adapter\FilesystemAdapterInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class SetonoSyliusVideoExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @test
     */
    public function it_sets_the_filesystem_parameter_and_alias(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('setono_sylius_video.filesystem.public_url_prefix', '/media/image');
        $this->assertContainerBuilderHasAlias('setono_sylius_video.filesystem', FilesystemAdapterInterface::class);
    }

    /**
     * @test
     */
    public function it_registers_the_embed_type_by_default(): void
    {
        $this->load();

        self::assertArrayHasKey('setono_sylius_video.embed_video', (array) $this->container->getParameter('sylius.resources'));
        $this->assertContainerBuilderHasService(EmbedProductVideoTypeExtension::class);
        $this->assertContainerBuilderHasService(EmbedProductVideoRenderer::class);
    }

    /**
     * @test
     */
    public function it_prepends_the_shop_product_page_block_when_sylius_ui_is_present(): void
    {
        $this->container->registerExtension(new class() extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'sylius_ui';
            }
        });

        (new SetonoSyliusVideoExtension())->prepend($this->container);

        self::assertSame([[
            'events' => [
                'sylius.shop.product.show.content' => [
                    'blocks' => [
                        'setono_sylius_video' => [
                            'template' => '@SetonoSyliusVideoPlugin/shop/product/_videos.html.twig',
                            'priority' => 12,
                        ],
                    ],
                ],
            ],
        ]], $this->container->getExtensionConfig('sylius_ui'));
    }

    /**
     * @test
     */
    public function it_removes_the_embed_type_when_disabled(): void
    {
        $this->load(['embed' => ['enabled' => false]]);

        $resources = (array) $this->container->getParameter('sylius.resources');
        self::assertArrayNotHasKey('setono_sylius_video.embed_video', $resources);
        self::assertArrayHasKey('setono_sylius_video.url_video', $resources);
        $this->assertContainerBuilderNotHasService(EmbedProductVideoTypeExtension::class);
        $this->assertContainerBuilderNotHasService(EmbedProductVideoRenderer::class);
    }

    /**
     * @test
     */
    public function it_prepends_nothing_without_sylius_ui(): void
    {
        (new SetonoSyliusVideoExtension())->prepend($this->container);

        self::assertSame([], $this->container->getExtensionConfig('sylius_ui'));
    }

    /**
     * @test
     */
    public function it_registers_the_plugin_services(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(ProductVideoDiscriminatorMapListener::class);
        $this->assertContainerBuilderHasService(CompositeVideoRenderer::class);
    }

    /**
     * @return list<SetonoSyliusVideoExtension>
     */
    protected function getContainerExtensions(): array
    {
        return [
            new SetonoSyliusVideoExtension(),
        ];
    }
}
