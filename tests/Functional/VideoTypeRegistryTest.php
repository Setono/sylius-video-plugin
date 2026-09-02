<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Functional;

use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Type\VideoTypeRegistryInterface;

final class VideoTypeRegistryTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_registers_the_built_in_types_with_labels_and_factories(): void
    {
        $registry = $this->service(VideoTypeRegistryInterface::class);

        self::assertSame(['file', 'url', 'embed'], $registry->getTypes());
        self::assertSame([
            'setono_sylius_video.ui.types.file' => 'file',
            'setono_sylius_video.ui.types.url' => 'url',
            'setono_sylius_video.ui.types.embed' => 'embed',
        ], $registry->getChoices());

        self::assertInstanceOf(FileProductVideo::class, $registry->getFactory('file')->createNew());
        self::assertInstanceOf(UrlProductVideo::class, $registry->getFactory('url')->createNew());
        self::assertInstanceOf(EmbedProductVideo::class, $registry->getFactory('embed')->createNew());
    }
}
