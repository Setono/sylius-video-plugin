<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

/**
 * The account's webhook subscription as Cloudflare Stream reports it. Cloudflare allows a single
 * subscription per account and returns its signing secret with it, both when it is created and
 * when it is read back.
 */
final class WebhookSubscription
{
    public function __construct(
        public readonly string $notificationUrl,
        public readonly string $secret,
        public readonly ?\DateTimeImmutable $modifiedAt = null,
    ) {
    }
}
