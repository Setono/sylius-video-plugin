<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Declares the public Twig functions. The actual work lives in {@see VideoRuntime}, which is
 * resolved lazily by Twig — this keeps the extension free of service dependencies and avoids a
 * circular reference (twig → extension → renderer → twig).
 */
final class VideoTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('setono_sylius_video_render', [VideoRuntime::class, 'render'], ['is_safe' => ['html']]),
            new TwigFunction('setono_sylius_video_poster', [VideoRuntime::class, 'poster']),
        ];
    }
}
