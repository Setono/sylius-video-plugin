<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Application\Renderer;

use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Renderer\VideoRendererInterface;
use Setono\SyliusVideoPlugin\Tests\Application\Entity\Video\YoutubeProductVideoInterface;
use Twig\Environment;

/**
 * Tagged `setono_sylius_video.renderer` with a priority above the plugin's URL renderer, which
 * would otherwise claim a YouTube video first (it is a URL video too).
 */
final class YoutubeProductVideoRenderer implements VideoRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function supports(ProductVideoInterface $video): bool
    {
        return $video instanceof YoutubeProductVideoInterface && null !== $video->getVideoId();
    }

    public function render(ProductVideoInterface $video): string
    {
        if (!$video instanceof YoutubeProductVideoInterface) {
            throw new UnsupportedVideoException($video);
        }

        return $this->twig->render('video/youtube.html.twig', [
            'video' => $video,
            'video_id' => $video->getVideoId(),
        ]);
    }
}
