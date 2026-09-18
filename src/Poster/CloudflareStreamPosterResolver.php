<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Poster;

use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamUrlGeneratorInterface;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

/**
 * Falls back to the thumbnail Cloudflare Stream generates from the video itself. Tagged below the
 * stored-poster resolver (priority 100), so an uploaded poster still wins; a video that is not
 * ready has no frames to take a thumbnail from yet.
 */
final class CloudflareStreamPosterResolver implements VideoPosterResolverInterface
{
    public function __construct(
        private readonly CloudflareStreamUrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(ProductVideoInterface $video): bool
    {
        return null !== $this->uidOfReadyVideo($video);
    }

    public function resolve(ProductVideoInterface $video): ?string
    {
        $uid = $this->uidOfReadyVideo($video);

        return null === $uid ? null : $this->urlGenerator->thumbnail($uid);
    }

    /**
     * The uid of a Cloudflare Stream video that has been processed, or null for anything else.
     */
    private function uidOfReadyVideo(ProductVideoInterface $video): ?string
    {
        if (!$video instanceof CloudflareStreamProductVideoInterface || !$video->isReady()) {
            return null;
        }

        return $video->getUid();
    }
}
