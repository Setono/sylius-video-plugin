<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\CloudflareStream;

use Psr\Clock\ClockInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Reads the webhook secret from Cloudflare Stream, which returns it with the account's subscription,
 * so no secret has to be copied into the application's configuration. The answer is cached: a
 * notification must not cost an API call, and a forced refresh (a signature that stopped matching)
 * is honoured at most once per interval so that forged notifications cannot burn the API quota.
 */
final class WebhookSecretProvider implements WebhookSecretProviderInterface
{
    public const CACHE_KEY = 'setono_sylius_video.cloudflare_stream.webhook_secret';

    private readonly ClockInterface $clock;

    public function __construct(
        private readonly WebhookClientInterface $client,
        private readonly CacheInterface $cache,
        /** How long a secret is remembered before Cloudflare is asked again */
        private readonly int $ttl = 3600,
        /** The least time between two refreshes, whatever asked for them */
        private readonly int $minimumRefreshInterval = 60,
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new class() implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }
        };
    }

    public function getSecret(bool $refresh = false): ?string
    {
        $entry = $this->entry();

        if ($refresh && $this->clock->now()->getTimestamp() - $entry['fetchedAt'] >= $this->minimumRefreshInterval) {
            $entry = $this->entry(\INF);
        }

        return $entry['secret'];
    }

    /**
     * @param float|null $beta INF forces the cached entry to be recomputed
     *
     * @return array{secret: ?string, fetchedAt: int}
     */
    private function entry(?float $beta = null): array
    {
        /** @var array{secret: ?string, fetchedAt: int} $entry */
        $entry = $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter($this->ttl);

            return [
                'secret' => $this->client->getWebhook()?->secret,
                'fetchedAt' => $this->clock->now()->getTimestamp(),
            ];
        }, $beta);

        return $entry;
    }
}
