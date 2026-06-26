<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Twig;

use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Poster\VideoPosterResolverInterface;
use Setono\SyliusVideoPlugin\Renderer\VideoRendererInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class VideoRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly VideoRendererInterface $renderer,
        private readonly VideoPosterResolverInterface $posterResolver,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(ProductVideoInterface $video, array $context = []): string
    {
        return $this->renderer->render($video, $context);
    }

    public function poster(ProductVideoInterface $video): ?string
    {
        return $this->posterResolver->resolve($video);
    }
}
