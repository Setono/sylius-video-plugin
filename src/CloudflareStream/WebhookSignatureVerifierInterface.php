<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

interface WebhookSignatureVerifierInterface
{
    /**
     * Whether the `Webhook-Signature` header proves the body was sent by Cloudflare Stream and signed
     * with the given secret, and is recent enough not to be a replay.
     */
    public function verify(string $secret, ?string $header, string $body): bool;
}
