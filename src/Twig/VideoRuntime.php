<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Twig;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Poster\VideoPosterResolverInterface;
use Setono\SyliusVideoPlugin\Renderer\VideoRendererInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class VideoRuntime implements RuntimeExtensionInterface
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly VideoRendererInterface $renderer,
        private readonly VideoPosterResolverInterface $posterResolver,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Renders the video, or nothing when no renderer supports its type. A type registered without
     * a renderer is a configuration mistake worth logging, not a reason to take the product page
     * down with a 500.
     */
    public function render(ProductVideoInterface $video): string
    {
        try {
            return $this->renderer->render($video);
        } catch (UnsupportedVideoException $e) {
            $this->logger->warning('Skipped a product video because no renderer supports its type. Tag a service implementing VideoRendererInterface with "setono_sylius_video.renderer" for it.', [
                'type' => $video::getType(),
                'video' => $video->getId(),
                'exception' => $e,
            ]);

            return '';
        }
    }

    public function poster(ProductVideoInterface $video): ?string
    {
        return $this->posterResolver->resolve($video);
    }
}
