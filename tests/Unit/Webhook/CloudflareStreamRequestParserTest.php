<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Webhook;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Clock\ClockInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSecretProviderInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSignatureVerifier;
use Setono\SyliusVideoPlugin\Webhook\CloudflareStreamRequestParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class CloudflareStreamRequestParserTest extends TestCase
{
    use ProphecyTrait;

    private const SECRET = 'whsec_test';

    public const NOW = 1_700_000_000;

    /**
     * @test
     */
    public function it_turns_a_signed_notification_into_a_remote_event_identified_by_the_video_uid(): void
    {
        $body = '{"uid":"video123","readyToStream":true,"status":{"state":"ready"}}';

        $event = $this->parser()->parse($this->request($body, $this->signature($body)), self::SECRET);

        self::assertNotNull($event);
        self::assertSame('cloudflare_stream.video', $event->getName());
        self::assertSame('video123', $event->getId());
        self::assertSame(['uid' => 'video123', 'readyToStream' => true, 'status' => ['state' => 'ready']], $event->getPayload());
    }

    /**
     * @test
     */
    public function it_rejects_a_notification_with_an_invalid_signature(): void
    {
        $body = '{"uid":"video123"}';

        $this->expectException(RejectWebhookException::class);
        $this->expectExceptionMessage('Invalid webhook signature.');

        $this->parser()->parse($this->request($body, 'time=' . self::NOW . ',sig1=deadbeef'), self::SECRET);
    }

    /**
     * @test
     */
    public function it_rejects_a_notification_without_a_signature(): void
    {
        $this->expectException(RejectWebhookException::class);
        $this->expectExceptionMessage('Invalid webhook signature.');

        $this->parser()->parse($this->request('{"uid":"video123"}', null), self::SECRET);
    }

    /**
     * @test
     */
    public function it_rejects_a_notification_signed_with_another_secret(): void
    {
        $body = '{"uid":"video123"}';

        $this->expectException(RejectWebhookException::class);

        $this->parser()->parse($this->request($body, $this->signature($body)), 'another-secret');
    }

    /**
     * @test
     */
    public function it_verifies_against_the_secret_read_from_cloudflare_when_none_is_configured(): void
    {
        $body = '{"uid":"video123"}';

        $provider = $this->prophesize(WebhookSecretProviderInterface::class);
        $provider->getSecret()->willReturn(self::SECRET);
        $provider->getSecret(true)->shouldNotBeCalled();

        $event = $this->parser($provider->reveal())->parse($this->request($body, $this->signature($body)), '');

        self::assertNotNull($event);
        self::assertSame('video123', $event->getId());
    }

    /**
     * @test
     */
    public function it_tries_a_freshly_read_secret_once_when_the_remembered_one_no_longer_matches(): void
    {
        $body = '{"uid":"video123"}';

        $provider = $this->prophesize(WebhookSecretProviderInterface::class);
        $provider->getSecret()->willReturn('rotated-away')->shouldBeCalledOnce();
        $provider->getSecret(true)->willReturn(self::SECRET)->shouldBeCalledOnce();

        $event = $this->parser($provider->reveal())->parse($this->request($body, $this->signature($body)), '');

        self::assertNotNull($event);
        self::assertSame('video123', $event->getId());
    }

    /**
     * @test
     */
    public function it_rejects_a_notification_matching_neither_the_remembered_nor_a_fresh_secret(): void
    {
        $body = '{"uid":"video123"}';

        $provider = $this->prophesize(WebhookSecretProviderInterface::class);
        $provider->getSecret()->willReturn('rotated-away');
        $provider->getSecret(true)->willReturn('still-not-it');

        $this->expectException(RejectWebhookException::class);
        $this->expectExceptionMessage('Invalid webhook signature.');

        $this->parser($provider->reveal())->parse($this->request($body, $this->signature($body)), '');
    }

    /**
     * @test
     */
    public function it_rejects_every_notification_while_the_account_has_no_subscription(): void
    {
        $body = '{"uid":"video123"}';

        $provider = $this->prophesize(WebhookSecretProviderInterface::class);
        $provider->getSecret()->willReturn(null);
        $provider->getSecret(true)->willReturn(null);

        $this->expectException(RejectWebhookException::class);
        $this->expectExceptionMessage('Invalid webhook signature.');

        $this->parser($provider->reveal())->parse($this->request($body, $this->signature($body)), '');
    }

    /**
     * @test
     */
    public function it_does_not_ask_cloudflare_for_the_secret_when_the_notification_carries_no_signature(): void
    {
        $provider = $this->prophesize(WebhookSecretProviderInterface::class);
        $provider->getSecret(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(RejectWebhookException::class);
        $this->expectExceptionMessage('Invalid webhook signature.');

        $this->parser($provider->reveal())->parse($this->request('{"uid":"video123"}', null), '');
    }

    /**
     * @test
     */
    public function it_prefers_the_configured_secret_over_the_one_read_from_cloudflare(): void
    {
        $body = '{"uid":"video123"}';
        $signedWithCloudflaresSecret = sprintf('time=%d,sig1=%s', self::NOW, hash_hmac('sha256', self::NOW . '.' . $body, 'cloudflares-secret'));

        $provider = $this->prophesize(WebhookSecretProviderInterface::class);
        $provider->getSecret(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(RejectWebhookException::class);
        $this->expectExceptionMessage('Invalid webhook signature.');

        $this->parser($provider->reveal())->parse($this->request($body, $signedWithCloudflaresSecret), self::SECRET);
    }

    /**
     * @test
     *
     * @dataProvider bodiesWithoutUid
     */
    public function it_rejects_a_signed_notification_without_a_uid(string $body): void
    {
        $this->expectException(RejectWebhookException::class);
        $this->expectExceptionMessage('Expected a JSON body with a "uid".');

        $this->parser()->parse($this->request($body, $this->signature($body)), self::SECRET);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function bodiesWithoutUid(): iterable
    {
        yield 'no uid' => ['{"readyToStream":true}'];
        yield 'empty uid' => ['{"uid":""}'];
        yield 'uid not a string' => ['{"uid":123}'];
    }

    /**
     * @test
     */
    public function it_rejects_anything_but_a_post_with_a_json_body(): void
    {
        $body = '{"uid":"video123"}';

        $get = Request::create('/webhook/cloudflare_stream', 'GET', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
        $get->headers->set('Webhook-Signature', $this->signature($body));

        try {
            $this->parser()->parse($get, self::SECRET);
            self::fail('A GET request must be rejected.');
        } catch (RejectWebhookException $e) {
            self::assertSame('Request does not match.', $e->getMessage());
        }

        // The signature is checked only once the request shape is right, so a non-JSON body is
        // refused before it is even looked at.
        $this->expectException(RejectWebhookException::class);
        $this->expectExceptionMessage('Request does not match.');

        $this->parser()->parse($this->request('nope', $this->signature('nope')), self::SECRET);
    }

    /**
     * @test
     */
    public function it_answers_a_successful_notification_with_202_and_a_refused_one_with_406(): void
    {
        $parser = $this->parser();

        self::assertSame(202, $parser->createSuccessfulResponse()->getStatusCode());
        self::assertSame(406, $parser->createRejectedResponse('Invalid webhook signature.')->getStatusCode());
        self::assertSame('Invalid webhook signature.', $parser->createRejectedResponse('Invalid webhook signature.')->getContent());
    }

    /**
     * Without a provider, a parser whose secret must come from the configuration: reading it from
     * Cloudflare would be a mistake.
     */
    private function parser(?WebhookSecretProviderInterface $secretProvider = null): CloudflareStreamRequestParser
    {
        $clock = new class() implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('@' . CloudflareStreamRequestParserTest::NOW);
            }
        };

        if (null === $secretProvider) {
            $provider = $this->prophesize(WebhookSecretProviderInterface::class);
            $provider->getSecret(Argument::cetera())->shouldNotBeCalled();
            $secretProvider = $provider->reveal();
        }

        return new CloudflareStreamRequestParser(new WebhookSignatureVerifier(300, $clock), $secretProvider);
    }

    private function request(string $body, ?string $signature): Request
    {
        $request = Request::create('/webhook/cloudflare_stream', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);

        if (null !== $signature) {
            $request->headers->set('Webhook-Signature', $signature);
        }

        return $request;
    }

    private function signature(string $body): string
    {
        return sprintf('time=%d,sig1=%s', self::NOW, hash_hmac('sha256', self::NOW . '.' . $body, self::SECRET));
    }
}
