<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

/**
 * A one-time tus upload created for the browser: the URL it PATCHes the file to and the uid the
 * video will have on Cloudflare Stream.
 */
final class DirectUpload
{
    public function __construct(
        public readonly string $uploadUrl,
        public readonly string $uid,
    ) {
    }
}
