<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Setono\SyliusVideoPlugin\DependencyInjection\SetonoSyliusVideoExtension;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\ProductVideoDiscriminatorMapListener;
use Setono\SyliusVideoPlugin\Renderer\CompositeVideoRenderer;
use Sylius\Component\Core\Filesystem\Adapter\FilesystemAdapterInterface;

final class SetonoSyliusVideoExtensionTest extends AbstractExtensionTestCase
{
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
    public function it_registers_the_plugin_services(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(ProductVideoDiscriminatorMapListener::class);
        $this->assertContainerBuilderHasService(CompositeVideoRenderer::class);
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
