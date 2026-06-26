<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Poster;

use Setono\CompositeCompilerPass\CompositeService;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

/**
 * Composite poster resolver — returns the first supporting resolver's `resolve()` (priority
 * ordered), or null if none supports. Symmetric with the renderer composite. The collection is
 * populated by setono/composite-compiler-pass (see the bundle's build()); the composite itself
 * stays untagged so it is not added to itself.
 *
 * @extends CompositeService<VideoPosterResolverInterface>
 */
final class CompositeVideoPosterResolver extends CompositeService implements VideoPosterResolverInterface
{
    public function supports(ProductVideoInterface $video): bool
    {
        foreach ($this->services as $resolver) {
            if ($resolver->supports($video)) {
                return true;
            }
        }

        return false;
    }

    public function resolve(ProductVideoInterface $video): ?string
    {
        foreach ($this->services as $resolver) {
            if ($resolver->supports($video)) {
                return $resolver->resolve($video);
            }
        }

        return null;
    }
}
