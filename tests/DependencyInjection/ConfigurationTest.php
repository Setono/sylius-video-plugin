<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\DependencyInjection\Configuration;
use Setono\SyliusVideoPlugin\Model\EmbedVideo;
use Setono\SyliusVideoPlugin\Model\EmbedVideoInterface;
use Setono\SyliusVideoPlugin\Model\FileVideo;
use Setono\SyliusVideoPlugin\Model\FileVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\UrlVideo;
use Setono\SyliusVideoPlugin\Model\UrlVideoInterface;
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
            'filesystem' => [
                'adapter' => FilesystemAdapterInterface::class,
                'public_url_prefix' => '/media/image',
            ],
            'resources' => [
                'product_video' => ['classes' => [
                    'model' => ProductVideo::class,
                    'interface' => ProductVideoInterface::class,
                    'repository' => ProductVideoRepository::class,
                    'factory' => Factory::class,
                ]],
                'file_video' => ['classes' => [
                    'model' => FileVideo::class,
                    'interface' => FileVideoInterface::class,
                    'factory' => Factory::class,
                ]],
                'url_video' => ['classes' => [
                    'model' => UrlVideo::class,
                    'interface' => UrlVideoInterface::class,
                    'factory' => Factory::class,
                ]],
                'embed_video' => ['classes' => [
                    'model' => EmbedVideo::class,
                    'interface' => EmbedVideoInterface::class,
                    'factory' => Factory::class,
                ]],
            ],
        ]);
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
