<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Controller\Webhook;

use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusVideoPlugin\CloudflareStream\WebhookSignatureVerifier;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives Cloudflare Stream's webhook, sent once a video has finished processing (successfully
 * or not), and marks the matching video ready. Cloudflare signs every notification; a request
 * without a valid signature is refused, so the endpoint can stay public.
 */
final class CloudflareStreamWebhookAction
{
    private readonly LoggerInterface $logger;

    /**
     * @param RepositoryInterface<CloudflareStreamProductVideoInterface> $repository
     */
    public function __construct(
        private readonly WebhookSignatureVerifier $signatureVerifier,
        private readonly RepositoryInterface $repository,
        private readonly ObjectManager $manager,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(Request $request): Response
    {
        $body = $request->getContent();

        if (!$this->signatureVerifier->verify($request->headers->get('Webhook-Signature'), $body)) {
            return new Response('Invalid webhook signature.', Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($body, true);
        $payload = is_array($payload) ? $payload : [];
        $uid = $payload['uid'] ?? null;

        if (!is_string($uid) || '' === $uid) {
            return new Response('Expected a JSON body with a "uid".', Response::HTTP_BAD_REQUEST);
        }

        $video = $this->repository->findOneBy(['uid' => $uid]);

        if (!$video instanceof CloudflareStreamProductVideoInterface) {
            $this->logger->info('Ignored a Cloudflare Stream webhook for unknown video "{uid}".', ['uid' => $uid]);

            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $status = is_array($payload['status'] ?? null) ? $payload['status'] : [];

        if ('error' === ($status['state'] ?? null)) {
            $this->logger->warning('Cloudflare Stream could not process video "{uid}": {reason}', [
                'uid' => $uid,
                'reason' => is_string($status['errorReasonText'] ?? null) ? $status['errorReasonText'] : 'unknown reason',
            ]);
        }

        $video->setReady(true === ($payload['readyToStream'] ?? false));
        $this->manager->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
