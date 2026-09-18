<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

/**
 * Builds the public playback and thumbnail URLs Cloudflare Stream serves from the account's
 * customer subdomain (`customer-<code>.cloudflarestream.com`).
 */
interface CloudflareStreamUrlGeneratorInterface
{
    public function hlsManifest(string $uid): string;

    public function dashManifest(string $uid): string;

    /**
     * Cloudflare's own Stream Player, to embed in an iframe; accepts its player options as query
     * parameters (poster, autoplay, muted, …).
     */
    public function player(string $uid): string;

    /**
     * @param string $time the frame to use, e.g. `1s` or `2m30s`
     * @param int $height height of the generated image in pixels; the width follows the aspect ratio
     */
    public function thumbnail(string $uid, string $time = '1s', int $height = 720): string;
}
