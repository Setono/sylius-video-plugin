<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

interface CloudflareStreamClientInterface
{
    /**
     * Creates a direct creator upload over tus for a file of the given size, so the browser can
     * send the file straight to Cloudflare without it passing through the application.
     *
     * @throws CloudflareStreamException
     */
    public function createDirectUpload(int $size, string $name): DirectUpload;

    /**
     * @throws CloudflareStreamException
     */
    public function getVideo(string $uid): VideoDetails;

    /**
     * Deletes the video from Cloudflare Stream. A video that no longer exists there is not an error.
     *
     * @throws CloudflareStreamException
     */
    public function deleteVideo(string $uid): void;
}
