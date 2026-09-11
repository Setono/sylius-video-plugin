<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

/**
 * A video hosted on Cloudflare Stream. The file never touches the application: the admin form
 * uploads it from the browser straight to Cloudflare (a direct creator upload over tus) and only
 * the resulting uid is submitted. Playback uses the HLS/DASH manifests Cloudflare serves.
 */
class CloudflareStreamProductVideo extends ProductVideo implements CloudflareStreamProductVideoInterface
{
    protected ?string $uid = null;

    protected bool $ready = false;

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(?string $uid): void
    {
        $this->uid = $uid;
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    public function setReady(bool $ready): void
    {
        $this->ready = $ready;
    }
}
