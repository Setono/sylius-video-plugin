<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClient;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClientInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamUrlGeneratorInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSignatureVerifier;
use Setono\SyliusVideoPlugin\Command\CloudflareStreamSyncCommand;
use Setono\SyliusVideoPlugin\Controller\Admin\CloudflareStreamDirectUploadAction;
use Setono\SyliusVideoPlugin\Controller\Webhook\CloudflareStreamWebhookAction;
use Setono\SyliusVideoPlugin\DependencyInjection\Configuration;
use Setono\SyliusVideoPlugin\DependencyInjection\SetonoSyliusVideoExtension;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\CloudflareStreamVideoRemovalListener;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\ProductVideoDiscriminatorMapListener;
use Setono\SyliusVideoPlugin\Form\Extension\CloudflareStreamProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Form\Extension\EmbedProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Poster\CloudflareStreamPosterResolver;
use Setono\SyliusVideoPlugin\Renderer\CloudflareStreamProductVideoRenderer;
use Setono\SyliusVideoPlugin\Renderer\CompositeVideoRenderer;
use Setono\SyliusVideoPlugin\Renderer\EmbedProductVideoRenderer;
use Sylius\Component\Core\Filesystem\Adapter\FilesystemAdapterInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class SetonoSyliusVideoExtensionTest extends AbstractExtensionTestCase
{
    private const CLOUDFLARE_STREAM = ['enabled' => true, 'account_id' => 'acc', 'api_token' => 'token', 'customer_subdomain' => 'abc'];

    private const DEFAULT_EVENTS = [
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
        $this->registerSyliusUi();

        (new SetonoSyliusVideoExtension())->prepend($this->container);

        self::assertSame([['events' => self::DEFAULT_EVENTS]], $this->container->getExtensionConfig('sylius_ui'));
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
     * @test
     */
    public function it_leaves_the_cloudflare_stream_type_out_by_default(): void
    {
        $this->load();

        self::assertArrayNotHasKey('setono_sylius_video.cloudflare_stream_video', (array) $this->container->getParameter('sylius.resources'));
        $this->assertContainerBuilderNotHasService(CloudflareStreamClient::class);
        $this->assertContainerBuilderNotHasService(CloudflareStreamProductVideoTypeExtension::class);
        $this->assertContainerBuilderNotHasService(CloudflareStreamProductVideoRenderer::class);
        self::assertFalse($this->container->hasParameter('setono_sylius_video.cloudflare_stream.account_id'));
    }

    /**
     * @test
     */
    public function it_registers_the_cloudflare_stream_type_when_enabled(): void
    {
        $this->load(['cloudflare_stream' => self::CLOUDFLARE_STREAM + ['max_duration_seconds' => 600]]);

        self::assertArrayHasKey('setono_sylius_video.cloudflare_stream_video', (array) $this->container->getParameter('sylius.resources'));

        $this->assertContainerBuilderHasParameter('setono_sylius_video.cloudflare_stream.account_id', 'acc');
        $this->assertContainerBuilderHasParameter('setono_sylius_video.cloudflare_stream.api_token', 'token');
        $this->assertContainerBuilderHasParameter('setono_sylius_video.cloudflare_stream.customer_subdomain', 'abc');
        $this->assertContainerBuilderHasParameter('setono_sylius_video.cloudflare_stream.webhook_secret', null);
        $this->assertContainerBuilderHasParameter('setono_sylius_video.cloudflare_stream.max_duration_seconds', 600);

        $this->assertContainerBuilderHasAlias(CloudflareStreamClientInterface::class, CloudflareStreamClient::class);
        $this->assertContainerBuilderHasService(CloudflareStreamUrlGeneratorInterface::class);
        $this->assertContainerBuilderHasService(WebhookSignatureVerifier::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CloudflareStreamProductVideoTypeExtension::class, 'form.type_extension');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CloudflareStreamProductVideoRenderer::class, 'setono_sylius_video.renderer');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CloudflareStreamPosterResolver::class, 'setono_sylius_video.poster_resolver');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CloudflareStreamVideoRemovalListener::class, 'doctrine.event_listener', ['event' => 'onFlush']);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CloudflareStreamVideoRemovalListener::class, 'doctrine.event_listener', ['event' => 'preUpdate']);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CloudflareStreamVideoRemovalListener::class, 'doctrine.event_listener', ['event' => 'postFlush']);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CloudflareStreamSyncCommand::class, 'console.command');

        self::assertTrue($this->container->getDefinition(CloudflareStreamDirectUploadAction::class)->isPublic());
        self::assertTrue($this->container->getDefinition(CloudflareStreamWebhookAction::class)->isPublic());
    }

    /**
     * @test
     */
    public function it_prepends_the_player_bootstrap_when_the_cloudflare_stream_type_is_enabled(): void
    {
        $this->registerSyliusUi();
        $this->container->loadFromExtension('setono_sylius_video', ['cloudflare_stream' => self::CLOUDFLARE_STREAM + ['video_js' => ['script' => 'https://cdn.example.com/video.js']]]);

        (new SetonoSyliusVideoExtension())->prepend($this->container);

        self::assertSame([['events' => self::DEFAULT_EVENTS + [
            'sylius.shop.layout.javascripts' => [
                'blocks' => [
                    'setono_sylius_video' => [
                        'template' => '@SetonoSyliusVideoPlugin/shop/layout/_javascripts.html.twig',
                        'context' => [
                            'video_js_script' => 'https://cdn.example.com/video.js',
                            'video_js_stylesheet' => Configuration::DEFAULT_VIDEO_JS_STYLESHEET,
                        ],
                    ],
                ],
            ],
        ]]], $this->container->getExtensionConfig('sylius_ui'));
    }

    /**
     * @test
     */
    public function it_lets_a_later_configuration_file_disable_the_player_bootstrap_again(): void
    {
        $this->registerSyliusUi();
        $this->container->loadFromExtension('setono_sylius_video', ['cloudflare_stream' => self::CLOUDFLARE_STREAM]);
        $this->container->loadFromExtension('setono_sylius_video', ['cloudflare_stream' => ['enabled' => false]]);

        (new SetonoSyliusVideoExtension())->prepend($this->container);

        self::assertSame([['events' => self::DEFAULT_EVENTS]], $this->container->getExtensionConfig('sylius_ui'));
    }

    /**
     * @test
     */
    public function it_ignores_a_cloudflare_stream_section_that_is_not_a_map_when_prepending(): void
    {
        $this->registerSyliusUi();
        $this->container->loadFromExtension('setono_sylius_video', ['cloudflare_stream' => 'nope']);

        (new SetonoSyliusVideoExtension())->prepend($this->container);

        self::assertSame([['events' => self::DEFAULT_EVENTS]], $this->container->getExtensionConfig('sylius_ui'));
    }

    /**
     * @test
     */
    public function it_prepends_the_default_video_js_urls_when_none_are_configured(): void
    {
        $this->registerSyliusUi();
        $this->container->loadFromExtension('setono_sylius_video', ['cloudflare_stream' => self::CLOUDFLARE_STREAM]);

        (new SetonoSyliusVideoExtension())->prepend($this->container);

        self::assertSame([['events' => self::DEFAULT_EVENTS + [
            'sylius.shop.layout.javascripts' => [
                'blocks' => [
                    'setono_sylius_video' => [
                        'template' => '@SetonoSyliusVideoPlugin/shop/layout/_javascripts.html.twig',
                        'context' => [
                            'video_js_script' => Configuration::DEFAULT_VIDEO_JS_SCRIPT,
                            'video_js_stylesheet' => Configuration::DEFAULT_VIDEO_JS_STYLESHEET,
                        ],
                    ],
                ],
            ],
        ]]], $this->container->getExtensionConfig('sylius_ui'));
    }

    private function registerSyliusUi(): void
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
