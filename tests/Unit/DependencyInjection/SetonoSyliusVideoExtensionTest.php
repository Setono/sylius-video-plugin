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
use Setono\SyliusVideoPlugin\DependencyInjection\SetonoSyliusVideoExtension;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\CloudflareStreamVideoRemovalListener;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\ProductVideoDiscriminatorMapListener;
use Setono\SyliusVideoPlugin\Form\Extension\CloudflareStreamProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Form\Extension\EmbedProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Poster\CloudflareStreamPosterResolver;
use Setono\SyliusVideoPlugin\Renderer\CloudflareStreamProductVideoRenderer;
use Setono\SyliusVideoPlugin\Renderer\CompositeVideoRenderer;
use Setono\SyliusVideoPlugin\Renderer\EmbedProductVideoRenderer;
use Setono\SyliusVideoPlugin\Webhook\CloudflareStreamRequestParser;
use Setono\SyliusVideoPlugin\Webhook\CloudflareStreamWebhookConsumer;
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
        $this->assertContainerBuilderHasService(CloudflareStreamRequestParser::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CloudflareStreamWebhookConsumer::class, 'remote_event.consumer', ['consumer' => 'cloudflare_stream']);
    }

    /**
     * @test
     */
    public function it_routes_cloudflares_webhook_through_the_framework_when_a_secret_is_configured(): void
    {
        $this->registerFramework();
        $this->container->loadFromExtension('setono_sylius_video', ['cloudflare_stream' => self::CLOUDFLARE_STREAM + ['webhook_secret' => '%env(CLOUDFLARE_STREAM_WEBHOOK_SECRET)%']]);

        (new SetonoSyliusVideoExtension())->prepend($this->container);

        self::assertSame([['webhook' => ['routing' => ['cloudflare_stream' => [
            'service' => CloudflareStreamRequestParser::class,
            'secret' => '%env(CLOUDFLARE_STREAM_WEBHOOK_SECRET)%',
        ]]]]], $this->container->getExtensionConfig('framework'));
    }

    /**
     * @test
     *
     * @dataProvider webhookRoutingLeftOut
     *
     * @param array<string, mixed> $config
     */
    public function it_leaves_the_webhook_unrouted_without_a_secret_or_while_the_type_is_disabled(array $config): void
    {
        $this->registerFramework();
        $this->container->loadFromExtension('setono_sylius_video', ['cloudflare_stream' => $config]);

        (new SetonoSyliusVideoExtension())->prepend($this->container);

        self::assertSame([], $this->container->getExtensionConfig('framework'));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function webhookRoutingLeftOut(): iterable
    {
        yield 'no secret' => [self::CLOUDFLARE_STREAM];
        yield 'empty secret' => [self::CLOUDFLARE_STREAM + ['webhook_secret' => '']];
        yield 'disabled' => [['enabled' => false, 'webhook_secret' => 'secret']];
        yield 'malformed section' => [[]];
    }

    /**
     * @test
     */
    public function it_does_not_touch_the_framework_configuration_when_the_framework_extension_is_absent(): void
    {
        $this->container->loadFromExtension('setono_sylius_video', ['cloudflare_stream' => self::CLOUDFLARE_STREAM + ['webhook_secret' => 'secret']]);

        (new SetonoSyliusVideoExtension())->prepend($this->container);

        self::assertSame([], $this->container->getExtensionConfig('framework'));
    }

    private function registerFramework(): void
    {
        $this->container->registerExtension(new class() extends Extension {
            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return 'framework';
            }
        });
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
