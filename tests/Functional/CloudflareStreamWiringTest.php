<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Functional;

use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClient;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamUrlGeneratorInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSecretProvider;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSecretProviderInterface;
use Setono\SyliusVideoPlugin\Controller\Admin\CloudflareStreamDirectUploadAction;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Webhook\CloudflareStreamRequestParser;
use Setono\SyliusVideoPlugin\Webhook\CloudflareStreamWebhookConsumer;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * The test application enables the Cloudflare Stream type (see config/packages/setono_sylius_video.yaml).
 */
final class CloudflareStreamWiringTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_registers_the_admin_upload_route(): void
    {
        // The plugin's route file is loaded on its own rather than through the router: the full
        // collection would pull in Sylius's shop routes, which reference Payum routing files that
        // some supported Payum bundle versions do not ship.
        $loader = self::getContainer()->get('routing.loader');
        self::assertInstanceOf(LoaderInterface::class, $loader);
        $routes = $loader->load('@SetonoSyliusVideoPlugin/Resources/config/routes.yaml');
        self::assertInstanceOf(RouteCollection::class, $routes);

        $upload = $routes->get('setono_sylius_video_admin_cloudflare_stream_direct_upload');
        self::assertNotNull($upload);
        self::assertSame('/%sylius_admin.path_name%/videos/cloudflare-stream/direct-upload', $upload->getPath());
        self::assertSame(['POST'], $upload->getMethods());
        self::assertSame(CloudflareStreamDirectUploadAction::class, $upload->getDefault('_controller'));

        // The webhook is not a route of the plugin: it goes through Symfony's webhook endpoint.
        self::assertNull($routes->get('setono_sylius_video_cloudflare_stream_webhook'));
    }

    /**
     * @test
     */
    public function it_routes_cloudflares_webhook_through_symfonys_webhook_component(): void
    {
        $container = self::getContainer();

        // The test application imports the framework's webhook route, so notifications arrive at /webhook/cloudflare_stream.
        $loader = $container->get('routing.loader');
        self::assertInstanceOf(LoaderInterface::class, $loader);
        $routes = $loader->load('@FrameworkBundle/Resources/config/routing/webhook.xml');
        self::assertInstanceOf(RouteCollection::class, $routes);
        self::assertNotNull($routes->get('_webhook_controller'));

        self::assertInstanceOf(CloudflareStreamRequestParser::class, $container->get(CloudflareStreamRequestParser::class));
        self::assertInstanceOf(CloudflareStreamWebhookConsumer::class, $container->get(CloudflareStreamWebhookConsumer::class));
    }

    /**
     * @test
     */
    public function it_exposes_the_controllers_and_the_commands(): void
    {
        $container = self::getContainer();

        self::assertInstanceOf(CloudflareStreamDirectUploadAction::class, $container->get(CloudflareStreamDirectUploadAction::class));

        $commands = $container->get('console.command_loader');
        self::assertInstanceOf(CommandLoaderInterface::class, $commands);
        self::assertTrue($commands->has('setono:sylius-video:cloudflare-stream:sync'));
        self::assertTrue($commands->has('setono:sylius-video:cloudflare-stream:subscribe-webhook'));
    }

    /**
     * @test
     */
    public function it_reads_the_webhook_secret_through_the_application_cache(): void
    {
        self::assertInstanceOf(WebhookSecretProvider::class, $this->service(WebhookSecretProviderInterface::class));
    }

    /**
     * @test
     */
    public function it_builds_the_client_and_urls_from_the_configuration(): void
    {
        self::assertInstanceOf(CloudflareStreamClient::class, self::getContainer()->get(CloudflareStreamClient::class));

        // .env sets the customer subdomain placeholder to `replace-me`.
        self::assertSame(
            'https://customer-replace-me.cloudflarestream.com/video123/manifest/video.m3u8',
            $this->service(CloudflareStreamUrlGeneratorInterface::class)->hlsManifest('video123'),
        );
    }

    /**
     * @test
     */
    public function it_registers_the_type_as_a_resource_with_a_repository(): void
    {
        $container = self::getContainer();

        self::assertTrue($container->has('setono_sylius_video.repository.cloudflare_stream_video'));
        self::assertTrue($container->has('setono_sylius_video.factory.cloudflare_stream_video'));
        self::assertSame(CloudflareStreamProductVideo::class, $container->getParameter('setono_sylius_video.model.cloudflare_stream_video.class'));
        self::assertSame(ProductVideo::class, $container->getParameter('setono_sylius_video.model.product_video.class'));
    }
}
