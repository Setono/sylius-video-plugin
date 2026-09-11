<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Functional;

use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClient;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamUrlGeneratorInterface;
use Setono\SyliusVideoPlugin\Controller\Admin\CloudflareStreamDirectUploadAction;
use Setono\SyliusVideoPlugin\Controller\Webhook\CloudflareStreamWebhookAction;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * The test application enables the Cloudflare Stream type (see config/packages/setono_sylius_video.yaml).
 */
final class CloudflareStreamWiringTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_registers_the_admin_upload_route_and_the_webhook_route(): void
    {
        $routes = $this->service(RouterInterface::class)->getRouteCollection();

        $upload = $routes->get('setono_sylius_video_admin_cloudflare_stream_direct_upload');
        self::assertNotNull($upload);
        self::assertSame('/admin/videos/cloudflare-stream/direct-upload', $upload->getPath());
        self::assertSame(['POST'], $upload->getMethods());
        self::assertSame(CloudflareStreamDirectUploadAction::class, $upload->getDefault('_controller'));

        $webhook = $routes->get('setono_sylius_video_cloudflare_stream_webhook');
        self::assertNotNull($webhook);
        self::assertSame('/setono-sylius-video/cloudflare-stream/webhook', $webhook->getPath());
        self::assertSame(['POST'], $webhook->getMethods());
        self::assertSame(CloudflareStreamWebhookAction::class, $webhook->getDefault('_controller'));
    }

    /**
     * @test
     */
    public function it_exposes_the_controllers_and_the_sync_command(): void
    {
        $container = self::getContainer();

        self::assertInstanceOf(CloudflareStreamDirectUploadAction::class, $container->get(CloudflareStreamDirectUploadAction::class));
        self::assertInstanceOf(CloudflareStreamWebhookAction::class, $container->get(CloudflareStreamWebhookAction::class));

        $commands = $container->get('console.command_loader');
        self::assertInstanceOf(CommandLoaderInterface::class, $commands);
        self::assertTrue($commands->has('setono:sylius-video:cloudflare-stream:sync'));
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
