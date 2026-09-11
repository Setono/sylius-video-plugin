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
        /** @var array{embed: array{enabled: bool}, cloudflare_stream: array{enabled: bool, account_id: ?string, api_token: ?string, customer_subdomain: ?string, webhook_secret: ?string, max_duration_seconds: ?int}, resources: array<string, mixed>, filesystem: array{adapter: string, public_url_prefix: string}} $config */
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

        if ($config['cloudflare_stream']['enabled']) {
            foreach (['account_id', 'api_token', 'customer_subdomain', 'webhook_secret', 'max_duration_seconds'] as $key) {
                $container->setParameter('setono_sylius_video.cloudflare_stream.' . $key, $config['cloudflare_stream'][$key]);
            }

            $loader->load('services/cloudflare_stream.xml');
        } else {
            // Opt-in type: without the resource it is absent from the STI map and the type selector,
            // and none of its services (which need the Cloudflare credentials) are registered.
            unset($config['resources']['cloudflare_stream_video']);
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
        if (!$container->hasExtension('sylius_ui')) {
            return;
        }

        // Render the product's videos on the shop product page. The `content` event always
        // fires on the product show page (unlike `before_thumbnails`, which only fires when a
        // product has more than one image). Priority 12 places the block after the product
        // tabs (20) and before the associations (10); apps can move it or disable it by
        // overriding this block's `priority` / `enabled`.
        $events = [
            // The stylesheet sizes pasted embed iframes inside the responsive box.
            'sylius.shop.layout.stylesheets' => [
                'blocks' => [
                    'setono_sylius_video' => [
                        'template' => '@SetonoSyliusVideoPlugin/shop/layout/_stylesheets.html.twig',
                    ],
                ],
            ],
            'sylius.shop.product.show.content' => [
                'blocks' => [
                    'setono_sylius_video' => [
                        'template' => '@SetonoSyliusVideoPlugin/shop/product/_videos.html.twig',
                        'priority' => 12,
                    ],
                ],
            ],
        ];

        $cloudflareStream = $this->rawCloudflareStreamConfig($container);

        // The Cloudflare Stream type needs the player bootstrap on the shop layout. The raw config
        // is inspected here because the merged configuration is not available yet in prepend();
        // env placeholders are passed through and resolved by the container later.
        if (false !== ($cloudflareStream['enabled'] ?? false)) {
            $videoJs = is_array($cloudflareStream['video_js'] ?? null) ? $cloudflareStream['video_js'] : [];

            $events['sylius.shop.layout.javascripts'] = [
                'blocks' => [
                    'setono_sylius_video' => [
                        'template' => '@SetonoSyliusVideoPlugin/shop/layout/_javascripts.html.twig',
                        'context' => [
                            'video_js_script' => $videoJs['script'] ?? Configuration::DEFAULT_VIDEO_JS_SCRIPT,
                            'video_js_stylesheet' => $videoJs['stylesheet'] ?? Configuration::DEFAULT_VIDEO_JS_STYLESHEET,
                        ],
                    ],
                ],
            ];
        }

        $container->prependExtensionConfig('sylius_ui', ['events' => $events]);
    }

    /**
     * The `cloudflare_stream` section as configured, later files overriding earlier ones key by key.
     *
     * @return array<string, mixed>
     */
    private function rawCloudflareStreamConfig(ContainerBuilder $container): array
    {
        $merged = [];

        foreach ($container->getExtensionConfig($this->getAlias()) as $config) {
            if (!is_array($config['cloudflare_stream'] ?? null)) {
                continue;
            }

            foreach ($config['cloudflare_stream'] as $key => $value) {
                $merged[(string) $key] = $value;
            }
        }

        return $merged;
    }
}
