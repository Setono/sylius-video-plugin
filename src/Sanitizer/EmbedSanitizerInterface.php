<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Sanitizer;

interface EmbedSanitizerInterface
{
    /**
     * Sanitizes admin-provided embed code before it is rendered raw in the shop. Decorate this
     * service to plug in an HTML sanitizer (e.g. an allow-list of iframe hosts).
     */
    public function sanitize(string $code): string;
}
