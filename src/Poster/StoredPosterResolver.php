<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Poster;

use Setono\SyliusVideoPlugin\Filesystem\MediaUrlGeneratorInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

/**
 * Type-agnostic, high-priority resolver: supports any video that has a stored poster
 * (`posterPath` set) and resolves it to a public URL via the media filesystem. When no poster is
 * stored it does not support the video, so a computed resolver (e.g. a YouTube thumbnail
 * resolver) can take over.
 */
final class StoredPosterResolver implements VideoPosterResolverInterface
{
    public function __construct(
        private readonly MediaUrlGeneratorInterface $mediaUrlGenerator,
    ) {
    }

    public function supports(ProductVideoInterface $video): bool
    {
        return null !== $video->getPosterPath();
    }

    public function resolve(ProductVideoInterface $video): ?string
    {
        $posterPath = $video->getPosterPath();

        if (null === $posterPath) {
            return null;
        }

        return $this->mediaUrlGenerator->generate($posterPath);
    }
}
