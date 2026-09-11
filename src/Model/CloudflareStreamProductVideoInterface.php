<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

interface CloudflareStreamProductVideoInterface extends ProductVideoInterface, ProcessingAwareVideoInterface
{
    /**
     * The Cloudflare Stream video identifier, assigned when the browser's direct upload is created.
     */
    public function getUid(): ?string;

    public function setUid(?string $uid): void;

    /**
     * Whether Cloudflare has finished processing the video (`readyToStream`); kept in sync by the
     * webhook and the sync command.
     */
    public function setReady(bool $ready): void;
}
