<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin;

use Setono\CompositeCompilerPass\CompositeCompilerPass;
use Setono\SyliusVideoPlugin\Poster\CompositeVideoPosterResolver;
use Setono\SyliusVideoPlugin\Renderer\CompositeVideoRenderer;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\Bundle\ResourceBundle\AbstractResourceBundle;
use Sylius\Bundle\ResourceBundle\SyliusResourceBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetonoSyliusVideoPlugin extends AbstractResourceBundle
{
    use SyliusPluginTrait;

    /**
     * @return list<string>
     */
    public function getSupportedDrivers(): array
    {
        return [
            SyliusResourceBundle::DRIVER_DOCTRINE_ORM,
        ];
    }

    protected function getModelNamespace(): string
    {
        return 'Setono\SyliusVideoPlugin\Model';
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Services tagged `setono_sylius_video.renderer` / `.poster_resolver` are auto-registered
        // (priority-sorted) onto the matching composite via setono/composite-compiler-pass. The
        // composites stay untagged so they are not added to themselves.
        $container->addCompilerPass(new CompositeCompilerPass(
            CompositeVideoRenderer::class,
            'setono_sylius_video.renderer',
        ));
        $container->addCompilerPass(new CompositeCompilerPass(
            CompositeVideoPosterResolver::class,
            'setono_sylius_video.poster_resolver',
        ));
    }
}
