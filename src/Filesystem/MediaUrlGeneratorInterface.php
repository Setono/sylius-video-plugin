<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Filesystem;

interface MediaUrlGeneratorInterface
{
    /**
     * Turns a path stored on the media filesystem into a public, browser-resolvable URL.
     */
    public function generate(string $path): string;
}
