<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\CloudflareStream;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClientInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSecretProvider;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSubscription;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class WebhookSecretProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_reads_the_secret_from_cloudflare_once_and_remembers_it(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getWebhook()->willReturn($this->subscription('whsec'))->shouldBeCalledOnce();

        $provider = new WebhookSecretProvider($client->reveal(), new ArrayAdapter(), clock: new FrozenClock(1_000));

        self::assertSame('whsec', $provider->getSecret());
        self::assertSame('whsec', $provider->getSecret());
    }

    /**
     * @test
     */
    public function it_remembers_that_the_account_has_no_subscription(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getWebhook()->willReturn(null)->shouldBeCalledOnce();

        $provider = new WebhookSecretProvider($client->reveal(), new ArrayAdapter(), clock: new FrozenClock(1_000));

        self::assertNull($provider->getSecret());
        self::assertNull($provider->getSecret());
    }

    /**
     * @test
     */
    public function it_reads_the_secret_again_on_a_refresh_but_not_more_often_than_the_minimum_interval(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getWebhook()->willReturn($this->subscription('old'), $this->subscription('rotated'))->shouldBeCalledTimes(2);

        $clock = new FrozenClock(1_000);
        $provider = new WebhookSecretProvider($client->reveal(), new ArrayAdapter(), clock: $clock);

        self::assertSame('old', $provider->getSecret());

        // A refresh within the interval (60 seconds by default) is not worth an API call.
        $clock->timestamp = 1_059;
        self::assertSame('old', $provider->getSecret(true));

        $clock->timestamp = 1_060;
        self::assertSame('rotated', $provider->getSecret(true));

        // The fresh secret is the remembered one from now on.
        $clock->timestamp = 1_200;
        self::assertSame('rotated', $provider->getSecret());
    }

    /**
     * @test
     */
    public function it_honours_a_configured_minimum_refresh_interval(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getWebhook()->willReturn($this->subscription('old'), $this->subscription('rotated'))->shouldBeCalledTimes(2);

        $clock = new FrozenClock(1_000);
        $provider = new WebhookSecretProvider($client->reveal(), new ArrayAdapter(), 3600, 10, $clock);

        self::assertSame('old', $provider->getSecret());

        $clock->timestamp = 1_010;
        self::assertSame('rotated', $provider->getSecret(true));
    }

    /**
     * @test
     */
    public function it_does_not_read_twice_when_a_refresh_finds_nothing_remembered(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getWebhook()->willReturn($this->subscription('whsec'))->shouldBeCalledOnce();

        $provider = new WebhookSecretProvider($client->reveal(), new ArrayAdapter(), clock: new FrozenClock(1_000));

        self::assertSame('whsec', $provider->getSecret(true));
    }

    /**
     * @test
     */
    public function it_lets_the_remembered_secret_expire_after_the_ttl(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getWebhook()->willReturn($this->subscription('whsec'));

        $item = $this->prophesize(ItemInterface::class);
        $item->expiresAfter(3600)->shouldBeCalledOnce()->willReturn($item);

        $cache = $this->prophesize(CacheInterface::class);
        $cache->get(WebhookSecretProvider::CACHE_KEY, Argument::type('callable'), null)->will(
            static function (array $arguments) use ($item): mixed {
                $callback = $arguments[1];
                self::assertIsCallable($callback);

                return $callback($item->reveal());
            },
        );

        $provider = new WebhookSecretProvider($client->reveal(), $cache->reveal(), clock: new FrozenClock(1_000));

        self::assertSame('whsec', $provider->getSecret());
    }

    /**
     * @test
     */
    public function it_uses_the_configured_ttl(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getWebhook()->willReturn(null);

        $item = $this->prophesize(ItemInterface::class);
        $item->expiresAfter(120)->shouldBeCalledOnce()->willReturn($item);

        $cache = $this->prophesize(CacheInterface::class);
        $cache->get(WebhookSecretProvider::CACHE_KEY, Argument::type('callable'), null)->will(
            static function (array $arguments) use ($item): mixed {
                $callback = $arguments[1];
                self::assertIsCallable($callback);

                return $callback($item->reveal());
            },
        );

        $provider = new WebhookSecretProvider($client->reveal(), $cache->reveal(), 120, clock: new FrozenClock(1_000));

        self::assertNull($provider->getSecret());
    }

    /**
     * @test
     */
    public function it_tells_the_system_time_by_default(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getWebhook()->willReturn($this->subscription('whsec'))->shouldBeCalledOnce();

        $provider = new WebhookSecretProvider($client->reveal(), new ArrayAdapter());

        // Read just now, so a refresh straight away is within the minimum interval.
        self::assertSame('whsec', $provider->getSecret());
        self::assertSame('whsec', $provider->getSecret(true));
    }

    /**
     * @test
     */
    public function it_passes_on_a_failure_to_ask_cloudflare(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getWebhook()->willThrow(new CloudflareStreamException('Authentication error'));

        $provider = new WebhookSecretProvider($client->reveal(), new ArrayAdapter(), clock: new FrozenClock(1_000));

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('Authentication error');

        $provider->getSecret();
    }

    private function subscription(string $secret): WebhookSubscription
    {
        return new WebhookSubscription('https://shop.test/webhook/cloudflare_stream', $secret);
    }
}

final class FrozenClock implements ClockInterface
{
    public function __construct(public int $timestamp)
    {
    }

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('@' . $this->timestamp);
    }
}
