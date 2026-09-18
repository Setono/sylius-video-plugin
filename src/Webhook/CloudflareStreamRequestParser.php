<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Webhook;

use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSignatureVerifierInterface;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

/**
 * Parses Cloudflare Stream's webhook for Symfony's Webhook component. The plugin registers it under
 * the `cloudflare_stream` type (`framework.webhook.routing`), so notifications arrive at
 * `/webhook/cloudflare_stream` on the framework's endpoint; the secret is the one Cloudflare returned
 * when the webhook was subscribed. A valid notification becomes a `cloudflare_stream.video` remote
 * event, identified by the video uid, that {@see CloudflareStreamWebhookConsumer} handles.
 */
final class CloudflareStreamRequestParser extends AbstractRequestParser
{
    /** The `framework.webhook.routing` type, and so the last path segment of the notification URL. */
    public const TYPE = 'cloudflare_stream';

    public const EVENT = 'cloudflare_stream.video';

    public function __construct(
        private readonly WebhookSignatureVerifierInterface $signatureVerifier,
    ) {
    }

    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new MethodRequestMatcher('POST'),
            new IsJsonRequestMatcher(),
        ]);
    }

    protected function doParse(Request $request, string $secret): RemoteEvent
    {
        $body = $request->getContent();

        if (!$this->signatureVerifier->verify($secret, $request->headers->get('Webhook-Signature'), $body)) {
            throw new RejectWebhookException(Response::HTTP_FORBIDDEN, 'Invalid webhook signature.');
        }

        $payload = json_decode($body, true);
        $payload = is_array($payload) ? $payload : [];
        $uid = $payload['uid'] ?? null;

        if (!is_string($uid) || '' === $uid) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Expected a JSON body with a "uid".');
        }

        return new RemoteEvent(self::EVENT, $uid, $payload);
    }
}
