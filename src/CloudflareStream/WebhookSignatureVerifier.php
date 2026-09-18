<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

use Psr\Clock\ClockInterface;

/**
 * Verifies the `Webhook-Signature` header Cloudflare Stream sends with every notification:
 * `time=<unix time>,sig1=<hex hmac>`, where the HMAC-SHA256 (keyed with the secret returned when
 * the webhook was subscribed) is computed over `<time>.<raw request body>`. Notifications older
 * than the tolerance are rejected so a captured request cannot be replayed later.
 */
final class WebhookSignatureVerifier
{
    private readonly ClockInterface $clock;

    public function __construct(
        private readonly int $toleranceInSeconds = 300,
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new class() implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }
        };
    }

    public function verify(string $secret, ?string $header, string $body): bool
    {
        if ('' === $secret || null === $header) {
            return false;
        }

        $parts = [];

        foreach (explode(',', $header) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, null);

            if (null !== $value) {
                $parts[$key] = $value;
            }
        }

        $time = $parts['time'] ?? null;
        $signature = $parts['sig1'] ?? null;

        if (null === $time || null === $signature || 1 !== preg_match('/^\d+$/', $time)) {
            return false;
        }

        if (abs($this->clock->now()->getTimestamp() - (int) $time) > $this->toleranceInSeconds) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $time . '.' . $body, $secret), strtolower($signature));
    }
}
