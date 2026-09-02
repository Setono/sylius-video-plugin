<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Twig;

use Setono\SyliusVideoPlugin\Filesystem\MediaUrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Backs `setono_sylius_video_media_url()`: the public URL of a stored video file or poster path,
 * used by the admin form to link to what a saved row currently holds.
 */
final class MediaUrlRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly MediaUrlGeneratorInterface $mediaUrlGenerator,
    ) {
    }

    public function generate(string $path): string
    {
        return $this->mediaUrlGenerator->generate($path);
    }
}
