<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\EventSubscriber;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\EventSubscriber\ProductFormMenuSubscriber;
use Sylius\Bundle\AdminBundle\Event\ProductMenuBuilderEvent;

final class ProductFormMenuSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_subscribes_to_the_product_form_menu_event(): void
    {
        self::assertSame(
            ['sylius.menu.admin.product.form' => 'addVideosTab'],
            ProductFormMenuSubscriber::getSubscribedEvents(),
        );
    }

    /**
     * @test
     */
    public function it_adds_a_videos_tab_with_the_plugin_template(): void
    {
        $videos = $this->prophesize(ItemInterface::class);
        $videos
            ->setAttribute('template', '@SetonoSyliusVideoPlugin/admin/product/tab/_videos.html.twig')
            ->shouldBeCalledOnce()
            ->willReturn($videos)
        ;
        $videos->setLabel('setono_sylius_video.ui.videos')->shouldBeCalledOnce()->willReturn($videos);

        $menu = $this->prophesize(ItemInterface::class);
        $menu->addChild('videos')->shouldBeCalledOnce()->willReturn($videos);

        $event = $this->prophesize(ProductMenuBuilderEvent::class);
        $event->getMenu()->willReturn($menu->reveal());

        (new ProductFormMenuSubscriber())->addVideosTab($event->reveal());
    }
}
