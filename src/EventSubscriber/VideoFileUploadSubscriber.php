<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\EventSubscriber;

use Setono\SyliusVideoPlugin\Model\FileProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideosAwareInterface;
use Setono\SyliusVideoPlugin\Uploader\VideoFileUploaderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Uploads pending video files and posters when a product is created or updated — mirrors Sylius's
 * own {@see \Sylius\Bundle\CoreBundle\EventListener\ImagesUploadListener}.
 */
final class VideoFileUploadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly VideoFileUploaderInterface $uploader,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product.pre_create' => 'upload',
            'sylius.product.pre_update' => 'upload',
        ];
    }

    public function upload(GenericEvent $event): void
    {
        $subject = $event->getSubject();

        if (!$subject instanceof ProductVideosAwareInterface) {
            return;
        }

        $videos = $subject->getVideos();

        foreach ($videos as $video) {
            if ($video instanceof FileProductVideoInterface && $video->hasFile()) {
                $this->uploader->upload($video);
            }

            // The poster upload is type-agnostic: any video (file/url/embed) may carry one.
            if ($video->hasPosterFile()) {
                $this->uploader->uploadPoster($video);
            }

            // A file video whose upload produced no path is unusable — drop it.
            if ($video instanceof FileProductVideoInterface && null === $video->getPath()) {
                $videos->removeElement($video);
            }
        }
    }
}
