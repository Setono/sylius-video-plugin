<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\EventSubscriber;

use Sylius\Bundle\AdminBundle\Event\ProductMenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds a "Videos" tab to the admin product create/edit form. The child name must equal the
 * pane's `data-tab` ("videos"); the tab pane is rendered from the `template` attribute by
 * Sylius's @SyliusAdmin/Product/_menu.html.twig.
 */
final class ProductFormMenuSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.menu.admin.product.form' => 'addVideosTab',
        ];
    }

    public function addVideosTab(ProductMenuBuilderEvent $event): void
    {
        $event->getMenu()
            ->addChild('videos')
            ->setAttribute('template', '@SetonoSyliusVideoPlugin/admin/product/tab/_videos.html.twig')
            ->setLabel('setono_sylius_video.ui.videos')
        ;
    }
}
