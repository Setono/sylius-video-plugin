<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\DependencyInjection;

use Setono\SyliusVideoPlugin\Form\Extension\EmbedProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Renderer\EmbedProductVideoRenderer;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class SetonoSyliusVideoExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    /**
     * @param array<array-key, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{embed: array{enabled: bool}, resources: array<string, mixed>, filesystem: array{adapter: string, public_url_prefix: string}} $config */
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);

        $container->setParameter('setono_sylius_video.filesystem.public_url_prefix', $config['filesystem']['public_url_prefix']);
        $container->setAlias('setono_sylius_video.filesystem', $config['filesystem']['adapter']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        if (!$config['embed']['enabled']) {
            // Without the resource the type is absent from both the STI map and the type selector;
            // its form extension and renderer would then only add dead weight.
            unset($config['resources']['embed_video']);
            $container->removeDefinition(EmbedProductVideoTypeExtension::class);
            $container->removeDefinition(EmbedProductVideoRenderer::class);
        }

        $this->registerResources(
            'setono_sylius_video',
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
            $config['resources'],
            $container,
        );
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('sylius_ui')) {
            // Render the product's videos on the shop product page. The `content` event always
            // fires on the product show page (unlike `before_thumbnails`, which only fires when a
            // product has more than one image). Disable by setting `enabled: false` on this block.
            $container->prependExtensionConfig('sylius_ui', [
                'events' => [
                    'sylius.shop.product.show.content' => [
                        'blocks' => [
                            'setono_sylius_video' => [
                                'template' => '@SetonoSyliusVideoPlugin/shop/product/_videos.html.twig',
                            ],
                        ],
                    ],
                ],
            ]);
        }
    }
}
