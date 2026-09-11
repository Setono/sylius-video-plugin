<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\DependencyInjection\Configuration;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Repository\ProductVideoRepository;
use Sylius\Component\Core\Filesystem\Adapter\FilesystemAdapterInterface;
use Sylius\Component\Resource\Factory\Factory;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    /**
     * @test
     */
    public function it_is_valid_without_any_configuration(): void
    {
        $this->assertConfigurationIsValid([[]]);
    }

    /**
     * @test
     */
    public function it_defaults_to_the_plugin_classes_and_sylius_media_storage(): void
    {
        $this->assertProcessedConfigurationEquals([[]], [
            'embed' => ['enabled' => true],
            'filesystem' => [
                'adapter' => FilesystemAdapterInterface::class,
                'public_url_prefix' => '/media/image',
            ],
            'cloudflare_stream' => [
                'enabled' => false,
                'account_id' => null,
                'api_token' => null,
                'customer_subdomain' => null,
                'webhook_secret' => null,
                'max_duration_seconds' => null,
                'video_js' => [
                    'script' => 'https://cdn.jsdelivr.net/npm/video.js@8.24.0/dist/video.min.js',
                    'stylesheet' => 'https://cdn.jsdelivr.net/npm/video.js@8.24.0/dist/video-js.min.css',
                ],
            ],
            'resources' => [
                'product_video' => ['classes' => [
                    'model' => ProductVideo::class,
                    'repository' => ProductVideoRepository::class,
                ]],
                'file_video' => ['classes' => [
                    'model' => FileProductVideo::class,
                    'factory' => Factory::class,
                ]],
                'url_video' => ['classes' => [
                    'model' => UrlProductVideo::class,
                    'factory' => Factory::class,
                ]],
                'embed_video' => ['classes' => [
                    'model' => EmbedProductVideo::class,
                    'factory' => Factory::class,
                ]],
                'cloudflare_stream_video' => ['classes' => [
                    'model' => CloudflareStreamProductVideo::class,
                    'factory' => Factory::class,
                ]],
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_accepts_the_cloudflare_stream_type_with_its_credentials(): void
    {
        $this->assertProcessedConfigurationEquals([[
            'cloudflare_stream' => [
                'enabled' => true,
                'account_id' => 'acc',
                'api_token' => 'token',
                'customer_subdomain' => 'abc',
                'webhook_secret' => 'secret',
                'max_duration_seconds' => 600,
                'video_js' => ['script' => 'https://cdn.example.com/video.js'],
            ],
        ]], ['cloudflare_stream' => [
            'enabled' => true,
            'account_id' => 'acc',
            'api_token' => 'token',
            'customer_subdomain' => 'abc',
            'webhook_secret' => 'secret',
            'max_duration_seconds' => 600,
            'video_js' => [
                'script' => 'https://cdn.example.com/video.js',
                'stylesheet' => 'https://cdn.jsdelivr.net/npm/video.js@8.24.0/dist/video-js.min.css',
            ],
        ]], 'cloudflare_stream');
    }

    /**
     * @test
     *
     * @dataProvider incompleteCloudflareStreamCredentials
     *
     * @param array<string, mixed> $config
     */
    public function it_requires_the_credentials_when_the_cloudflare_stream_type_is_enabled(array $config): void
    {
        $this->assertConfigurationIsInvalid(
            [['cloudflare_stream' => ['enabled' => true] + $config]],
            'The Cloudflare Stream type needs "account_id", "api_token" and "customer_subdomain" when it is enabled.',
        );
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function incompleteCloudflareStreamCredentials(): iterable
    {
        yield 'nothing' => [[]];
        yield 'no account' => [['api_token' => 'token', 'customer_subdomain' => 'abc']];
        yield 'no token' => [['account_id' => 'acc', 'customer_subdomain' => 'abc']];
        yield 'no subdomain' => [['account_id' => 'acc', 'api_token' => 'token']];
        yield 'empty token' => [['account_id' => 'acc', 'api_token' => '', 'customer_subdomain' => 'abc']];
    }

    /**
     * @test
     */
    public function it_does_not_require_credentials_while_the_cloudflare_stream_type_is_disabled(): void
    {
        $this->assertConfigurationIsValid([['cloudflare_stream' => ['enabled' => false, 'account_id' => 'acc']]]);
    }

    /**
     * @test
     */
    public function it_rejects_a_non_positive_maximum_duration(): void
    {
        $this->assertConfigurationIsInvalid([['cloudflare_stream' => ['max_duration_seconds' => 0]]], 'greater than or equal to 1');
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
