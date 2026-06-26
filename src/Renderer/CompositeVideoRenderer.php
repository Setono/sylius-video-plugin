<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Renderer;

use Setono\CompositeCompilerPass\CompositeService;
use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

/**
 * Composite renderer — dispatches to the first service tagged `setono_sylius_video.renderer`
 * whose `supports()` returns true. The collection is populated by setono/composite-compiler-pass
 * (see the bundle's build()); the composite itself stays untagged so it is not added to itself.
 *
 * @extends CompositeService<VideoRendererInterface>
 */
final class CompositeVideoRenderer extends CompositeService implements VideoRendererInterface
{
    public function supports(ProductVideoInterface $video): bool
    {
        foreach ($this->services as $renderer) {
            if ($renderer->supports($video)) {
                return true;
            }
        }

        return false;
    }

    public function render(ProductVideoInterface $video, array $context = []): string
    {
        foreach ($this->services as $renderer) {
            if ($renderer->supports($video)) {
                return $renderer->render($video, $context);
            }
        }

        throw new UnsupportedVideoException($video);
    }
}
