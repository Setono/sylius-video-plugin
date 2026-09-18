<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

/**
 * The part of the Cloudflare Stream API that manages the account's webhook. Cloudflare Stream
 * allows a single subscription per account.
 */
interface WebhookClientInterface
{
    /**
     * The account's webhook subscription, or null when the account has none. The subscription
     * carries the secret the notifications are signed with.
     *
     * @throws CloudflareStreamException
     */
    public function getWebhook(): ?WebhookSubscription;

    /**
     * Subscribes the account's webhook to the URL, replacing whatever subscription the account had.
     *
     * @throws CloudflareStreamException
     */
    public function subscribeWebhook(string $notificationUrl): WebhookSubscription;
}
