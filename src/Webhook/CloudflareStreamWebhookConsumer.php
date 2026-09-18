<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Webhook;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

/**
 * Handles the remote events {@see CloudflareStreamRequestParser} produces: Cloudflare sends one once
 * a video has finished processing (successfully or not), and the matching video is marked ready
 * accordingly. Runs through Messenger, synchronously unless the application routes
 * `ConsumeRemoteEventMessage` to a transport.
 */
final class CloudflareStreamWebhookConsumer implements ConsumerInterface
{
    use ORMTrait;

    private readonly LoggerInterface $logger;

    /**
     * @param RepositoryInterface<CloudflareStreamProductVideoInterface> $repository
     */
    public function __construct(
        private readonly RepositoryInterface $repository,
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger ?? new NullLogger();
    }

    public function consume(RemoteEvent $event): void
    {
        $uid = $event->getId();
        $video = $this->repository->findOneBy(['uid' => $uid]);

        if (!$video instanceof CloudflareStreamProductVideoInterface) {
            $this->logger->info('Ignored a Cloudflare Stream webhook for unknown video "{uid}".', ['uid' => $uid]);

            return;
        }

        $payload = $event->getPayload();
        $status = is_array($payload['status'] ?? null) ? $payload['status'] : [];

        if ('error' === ($status['state'] ?? null)) {
            $this->logger->warning('Cloudflare Stream could not process video "{uid}": {reason}', [
                'uid' => $uid,
                'reason' => is_string($status['errorReasonText'] ?? null) ? $status['errorReasonText'] : 'unknown reason',
            ]);
        }

        $video->setReady(true === ($payload['readyToStream'] ?? false));
        $this->getManager($video)->flush();
    }
}
