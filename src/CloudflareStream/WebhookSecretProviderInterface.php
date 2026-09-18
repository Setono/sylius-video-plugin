<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

interface WebhookSecretProviderInterface
{
    /**
     * Returns the secret Cloudflare Stream signs the account's webhook notifications with, or null
     * when the account has no webhook subscription. A refresh discards any remembered secret first,
     * for when the subscription has just been (re)created.
     *
     * @throws CloudflareStreamException when Cloudflare cannot be asked
     */
    public function getSecret(bool $refresh = false): ?string;
}
