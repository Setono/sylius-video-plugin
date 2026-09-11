<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

/**
 * The subset of Cloudflare Stream's video details the plugin acts on.
 */
final class VideoDetails
{
    public function __construct(
        public readonly string $uid,
        public readonly bool $readyToStream,
        /** Cloudflare's processing state: e.g. `pendingupload`, `queued`, `inprogress`, `ready` or `error`. */
        public readonly string $state,
        public readonly ?string $errorReason = null,
    ) {
    }
}
