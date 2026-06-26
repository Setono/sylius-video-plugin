<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Filesystem;

/**
 * Default generator: prefixes the stored path with the configured public base
 * (`setono_sylius_video.filesystem.public_url_prefix`). With the default Sylius media storage
 * (`sylius.storage` mounted at `public/media/image`) a stored path of `video/ab/cd.mp4` becomes
 * `/media/image/video/ab/cd.mp4`.
 */
final class MediaUrlGenerator implements MediaUrlGeneratorInterface
{
    public function __construct(
        private readonly string $publicUrlPrefix,
    ) {
    }

    public function generate(string $path): string
    {
        return rtrim($this->publicUrlPrefix, '/') . '/' . ltrim($path, '/');
    }
}
