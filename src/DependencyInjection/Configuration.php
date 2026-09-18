<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\DependencyInjection;

use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Repository\ProductVideoRepository;
use Sylius\Component\Core\Filesystem\Adapter\FilesystemAdapterInterface;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('setono_sylius_video');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('embed')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->info('The embed type prints admin-supplied HTML unescaped on the product page. Set to false to remove the type entirely if your shop does not need it.')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('filesystem')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('adapter')
                            ->info('Service id of the media filesystem used to store uploaded videos and posters. Defaults to the same storage Sylius uses for images.')
                            ->defaultValue(FilesystemAdapterInterface::class)
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('public_url_prefix')
                            ->info('Public URL base that a stored media path is prefixed with. Defaults to the public path of the default Sylius media storage.')
                            ->defaultValue('/media/image')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        $this->addCloudflareStreamSection($rootNode);
        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    private function addCloudflareStreamSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('cloudflare_stream')
                    ->info('The Cloudflare Stream video type: videos are uploaded from the admin\'s browser straight to Cloudflare Stream and played through Cloudflare\'s Stream Player (or a player of your own via a template override).')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->info('Adds the type to the type selector and registers its services and routes. Requires account_id, api_token and customer_subdomain.')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('account_id')
                            ->info('The Cloudflare account id that owns the Stream subscription.')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('api_token')
                            ->info('An API token with the "Stream: Edit" permission; used to create uploads, read video details and delete videos.')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('customer_subdomain')
                            ->info('The account\'s customer code from the Stream dashboard ("customer-<code>.cloudflarestream.com"); the code alone, or the full subdomain, are both accepted.')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('webhook_secret')
                            ->info('Optional. Pins the secret notifications are verified with; by default the plugin reads it from Cloudflare, which returns it with the webhook subscription (see the subscribe-webhook command).')
                            ->defaultNull()
                        ->end()
                        ->integerNode('max_duration_seconds')
                            ->info('Optional maximum duration of an uploaded video, enforced by Cloudflare when the upload is created.')
                            ->defaultNull()
                            ->min(1)
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(static function (array $config): bool {
                            if (true !== ($config['enabled'] ?? false)) {
                                return false;
                            }

                            foreach (['account_id', 'api_token', 'customer_subdomain'] as $key) {
                                if (!is_string($config[$key] ?? null) || '' === $config[$key]) {
                                    return true;
                                }
                            }

                            return false;
                        })
                        ->thenInvalid('The Cloudflare Stream type needs "account_id", "api_token" and "customer_subdomain" when it is enabled.')
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addResourcesSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('resources')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('product_video')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        // The base class is abstract, so it deliberately has no factory: only the
                                        // concrete subtype resources below can create videos.
                                        ->scalarNode('model')->defaultValue(ProductVideo::class)->cannotBeEmpty()->end()
                                        ->scalarNode('repository')->defaultValue(ProductVideoRepository::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('file_video')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(FileProductVideo::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('url_video')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(UrlProductVideo::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('embed_video')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(EmbedProductVideo::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('cloudflare_stream_video')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->variableNode('options')->end()
                                ->arrayNode('classes')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('model')->defaultValue(CloudflareStreamProductVideo::class)->cannotBeEmpty()->end()
                                        ->scalarNode('factory')->defaultValue(Factory::class)->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
