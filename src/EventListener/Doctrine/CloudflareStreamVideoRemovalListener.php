<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClientInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;

/**
 * Deletes videos from Cloudflare Stream when their row is removed, and when a saved row is given
 * a new uid by a re-upload (the video the old uid pointed to would otherwise linger, billed, on
 * Cloudflare). Uids are collected during the flush and deleted only after it succeeded, so a
 * failed transaction keeps its videos — mirrors ProductVideoFilesRemovalListener. A failed delete
 * is logged, never thrown: the product change itself has already been committed.
 */
final class CloudflareStreamVideoRemovalListener
{
    /** @var list<string> */
    private array $uidsToDelete = [];

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly CloudflareStreamClientInterface $client,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        foreach ($event->getObjectManager()->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof CloudflareStreamProductVideoInterface) {
                $this->collect($entity->getUid());
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $entity = $event->getObject();

        if (!$entity instanceof CloudflareStreamProductVideoInterface || !$event->hasChangedField('uid')) {
            return;
        }

        $previousUid = $event->getOldValue('uid');

        if (is_string($previousUid) && $previousUid !== $entity->getUid()) {
            $this->collect($previousUid);
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        $uids = $this->uidsToDelete;
        $this->uidsToDelete = [];

        foreach ($uids as $uid) {
            try {
                $this->client->deleteVideo($uid);
            } catch (CloudflareStreamException $e) {
                $this->logger->error('Could not delete video "{uid}" from Cloudflare Stream: {message}', [
                    'uid' => $uid,
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }
    }

    private function collect(?string $uid): void
    {
        if (null === $uid || '' === $uid || in_array($uid, $this->uidsToDelete, true)) {
            return;
        }

        $this->uidsToDelete[] = $uid;
    }
}
