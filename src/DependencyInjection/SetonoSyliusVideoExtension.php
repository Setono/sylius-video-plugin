<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\DependencyInjection;

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
        /** @var array{resources: array<string, mixed>, filesystem: array{adapter: string, public_url_prefix: string}} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        // Exposed for the discriminator-map listener — it needs the model class of each resource
        // so it can wire up the STI map at loadClassMetadata time.
        $container->setParameter('setono_sylius_video.resources', $config['resources']);
        $container->setParameter('setono_sylius_video.filesystem.public_url_prefix', $config['filesystem']['public_url_prefix']);
        $container->setAlias('setono_sylius_video.filesystem', $config['filesystem']['adapter']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->registerResources(
            'setono_sylius_video',
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
            $config['resources'],
            $container,
        );
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('kernel.bundles')) {
            return;
        }

        /** @var array<string, mixed> $bundles */
        $bundles = (array) $container->getParameter('kernel.bundles');

        if (isset($bundles['SyliusUiBundle'])) {
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
