<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\CloudflareStream;

use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSignatureVerifier;

final class WebhookSignatureVerifierTest extends TestCase
{
    private const SECRET = 'whsec_test';

    private const BODY = '{"uid":"video123","readyToStream":true}';

    /**
     * @test
     */
    public function it_accepts_a_notification_signed_with_the_secret(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET, 300, $this->clock(1_700_000_000));

        self::assertTrue($verifier->verify($this->header(1_700_000_000), self::BODY));
    }

    /**
     * @test
     */
    public function it_accepts_a_notification_within_the_tolerance(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET, 300, $this->clock(1_700_000_300));

        self::assertTrue($verifier->verify($this->header(1_700_000_000), self::BODY));
    }

    /**
     * @test
     */
    public function it_accepts_an_uppercase_hex_signature(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET, 300, $this->clock(1_700_000_000));

        self::assertTrue($verifier->verify(sprintf('time=%d,sig1=%s', 1_700_000_000, strtoupper(hash_hmac('sha256', '1700000000.' . self::BODY, self::SECRET))), self::BODY));
    }

    /**
     * @test
     */
    public function it_rejects_a_notification_older_than_the_tolerance(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET, 300, $this->clock(1_700_000_301));

        self::assertFalse($verifier->verify($this->header(1_700_000_000), self::BODY));
    }

    /**
     * @test
     */
    public function it_rejects_a_notification_from_the_future_beyond_the_tolerance(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET, 300, $this->clock(1_699_999_699));

        self::assertFalse($verifier->verify($this->header(1_700_000_000), self::BODY));
    }

    /**
     * @test
     */
    public function it_rejects_a_signature_made_with_another_secret(): void
    {
        $verifier = new WebhookSignatureVerifier('other', 300, $this->clock(1_700_000_000));

        self::assertFalse($verifier->verify($this->header(1_700_000_000), self::BODY));
    }

    /**
     * @test
     */
    public function it_rejects_a_tampered_body(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET, 300, $this->clock(1_700_000_000));

        self::assertFalse($verifier->verify($this->header(1_700_000_000), '{"uid":"other"}'));
    }

    /**
     * @test
     *
     * @dataProvider malformedHeaders
     */
    public function it_rejects_a_malformed_header(?string $header): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET, 300, $this->clock(1_700_000_000));

        self::assertFalse($verifier->verify($header, self::BODY));
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function malformedHeaders(): iterable
    {
        yield 'missing' => [null];
        yield 'empty' => [''];
        yield 'no signature' => ['time=1700000000'];
        yield 'no time' => ['sig1=abc'];
        yield 'time not numeric' => ['time=now,sig1=abc'];
        yield 'bare words' => ['time,sig1'];
    }

    /**
     * @test
     *
     * @dataProvider missingSecrets
     */
    public function it_rejects_everything_without_a_secret(?string $secret): void
    {
        $verifier = new WebhookSignatureVerifier($secret, 300, $this->clock(1_700_000_000));

        self::assertFalse($verifier->verify($this->header(1_700_000_000), self::BODY));
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function missingSecrets(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
    }

    /**
     * @test
     */
    public function it_uses_the_system_clock_by_default(): void
    {
        $verifier = new WebhookSignatureVerifier(self::SECRET);

        self::assertTrue($verifier->verify($this->header(time()), self::BODY));
        self::assertFalse($verifier->verify($this->header(time() - 3600), self::BODY));
    }

    private function header(int $time): string
    {
        return sprintf('time=%d,sig1=%s', $time, hash_hmac('sha256', $time . '.' . self::BODY, self::SECRET));
    }

    private function clock(int $timestamp): ClockInterface
    {
        return new class($timestamp) implements ClockInterface {
            public function __construct(private readonly int $timestamp)
            {
            }

            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('@' . $this->timestamp);
            }
        };
    }
}
