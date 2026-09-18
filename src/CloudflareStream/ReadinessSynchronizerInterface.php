<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;

interface ReadinessSynchronizerInterface
{
    /**
     * Fetches the video's current state from Cloudflare Stream, updates the video's readiness
     * accordingly and returns the details it was based on.
     *
     * @throws CloudflareStreamException when the video has no uid or Cloudflare cannot be asked
     */
    public function sync(CloudflareStreamProductVideoInterface $video): VideoDetails;
}
