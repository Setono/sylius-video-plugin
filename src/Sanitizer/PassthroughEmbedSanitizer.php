<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Sanitizer;

/**
 * Default sanitizer: returns the embed code unchanged. Admins are trusted to paste valid embed
 * markup; decorate {@see EmbedSanitizerInterface} to enforce a stricter policy.
 */
final class PassthroughEmbedSanitizer implements EmbedSanitizerInterface
{
    public function sanitize(string $code): string
    {
        return $code;
    }
}
