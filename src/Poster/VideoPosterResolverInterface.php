<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Poster;

use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

interface VideoPosterResolverInterface
{
    public function supports(ProductVideoInterface $video): bool;

    /**
     * Absolute or root-relative URL of a poster/thumbnail image, or null if none is available.
     */
    public function resolve(ProductVideoInterface $video): ?string;
}
