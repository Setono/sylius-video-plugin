<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

/**
 * A video whose provider processes it after the upload (transcoding, encoding). Until it is ready
 * the shop has nothing to play, so renderers output nothing and the plugin's product block skips
 * it; `setono_sylius_video_ready()` exposes the state to templates that list videos themselves.
 */
interface ProcessingAwareVideoInterface
{
    public function isReady(): bool;
}
