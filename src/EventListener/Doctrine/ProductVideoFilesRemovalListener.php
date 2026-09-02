<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\EventListener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Setono\SyliusVideoPlugin\Model\FileProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Uploader\VideoFileUploaderInterface;

/**
 * Deletes the stored video file and poster of every video Doctrine removes — mirrors Sylius's
 * {@see \Sylius\Bundle\CoreBundle\EventListener\ImagesRemoveListener}. Paths are collected during
 * onFlush, while the entities are still hydrated, and deleted only after the flush succeeded, so a
 * failed transaction keeps its files. Removing a product ends up here as well, because the
 * documented inverse mapping uses orphan removal, which cascades the removal to its videos.
 */
final class ProductVideoFilesRemovalListener
{
    /** @var list<string> */
    private array $pathsToDelete = [];

    public function __construct(
        private readonly VideoFileUploaderInterface $uploader,
    ) {
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        foreach ($event->getObjectManager()->getUnitOfWork()->getScheduledEntityDeletions() as $entity) {
            if (!$entity instanceof ProductVideoInterface) {
                continue;
            }

            $this->collect($entity->getPosterPath());

            if ($entity instanceof FileProductVideoInterface) {
                $this->collect($entity->getPath());
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        $paths = $this->pathsToDelete;
        $this->pathsToDelete = [];

        foreach ($paths as $path) {
            $this->uploader->remove($path);
        }
    }

    private function collect(?string $path): void
    {
        if (null === $path || in_array($path, $this->pathsToDelete, true)) {
            return;
        }

        $this->pathsToDelete[] = $path;
    }
}
