<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Menu;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Menu\ProductFormMenuListener;
use Sylius\Bundle\AdminBundle\Event\ProductMenuBuilderEvent;

final class ProductFormMenuListenerTest extends TestCase
{
    use ProphecyTrait;

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

        (new ProductFormMenuListener())($event->reveal());
    }
}
