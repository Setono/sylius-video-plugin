<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Application\Poster;

use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Poster\VideoPosterResolverInterface;
use Setono\SyliusVideoPlugin\Tests\Application\Entity\Video\YoutubeProductVideoInterface;

/**
 * Tagged `setono_sylius_video.poster_resolver` below the plugin's stored-poster resolver (priority
 * 100), so an uploaded poster still wins over the computed thumbnail.
 */
final class YoutubePosterResolver implements VideoPosterResolverInterface
{
    public function supports(ProductVideoInterface $video): bool
    {
        return $video instanceof YoutubeProductVideoInterface && null !== $video->getVideoId();
    }

    public function resolve(ProductVideoInterface $video): ?string
    {
        \assert($video instanceof YoutubeProductVideoInterface);

        return null === $video->getVideoId()
            ? null
            : sprintf('https://img.youtube.com/vi/%s/hqdefault.jpg', $video->getVideoId());
    }
}
