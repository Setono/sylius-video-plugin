<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\DependencyInjection\Configuration;
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
            ],
        ]);
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
