<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Webhook;

use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSecretProviderInterface;
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
 * `/webhook/cloudflare_stream` on the framework's endpoint. The signature is checked against the
 * configured `webhook_secret` or, by default, the secret read back from Cloudflare (see
 * {@see WebhookSecretProviderInterface}). A valid notification becomes a `cloudflare_stream.video`
 * remote event, identified by the video uid, that {@see CloudflareStreamWebhookConsumer} handles.
 */
final class CloudflareStreamRequestParser extends AbstractRequestParser
{
    /** The `framework.webhook.routing` type, and so the last path segment of the notification URL. */
    public const TYPE = 'cloudflare_stream';

    public const EVENT = 'cloudflare_stream.video';

    public function __construct(
        private readonly WebhookSignatureVerifierInterface $signatureVerifier,
        private readonly WebhookSecretProviderInterface $secretProvider,
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

        if (!$this->isSigned($request->headers->get('Webhook-Signature'), $body, $secret)) {
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

    /**
     * A secret configured for the type (`framework.webhook.routing`) is authoritative. Without one
     * the secret is the one Cloudflare holds for the account's subscription: a signature that does
     * not match the remembered secret is tried once more against a freshly read one, so that
     * re-subscribing (which may change the secret) takes effect without any restart. A notification
     * without a signature is refused without asking Cloudflare anything.
     */
    private function isSigned(?string $header, string $body, string $configuredSecret): bool
    {
        if ('' !== $configuredSecret) {
            return $this->signatureVerifier->verify($configuredSecret, $header, $body);
        }

        if (null === $header) {
            return false;
        }

        $secret = $this->secretProvider->getSecret();

        if (null !== $secret && $this->signatureVerifier->verify($secret, $header, $body)) {
            return true;
        }

        $secret = $this->secretProvider->getSecret(true);

        return null !== $secret && $this->signatureVerifier->verify($secret, $header, $body);
    }
}
