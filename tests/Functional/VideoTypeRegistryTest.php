<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Functional;

use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Tests\Application\Entity\Video\YoutubeProductVideo;
use Setono\SyliusVideoPlugin\Type\VideoTypeRegistryInterface;

final class VideoTypeRegistryTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_registers_the_built_in_types_and_the_test_applications_type_with_labels_and_factories(): void
    {
        $registry = $this->service(VideoTypeRegistryInterface::class);

        // The built-in types plus the README's youtube example, which the test application registers.
        self::assertEqualsCanonicalizing(['file', 'url', 'embed', 'youtube'], $registry->getTypes());
        self::assertEquals([
            'setono_sylius_video.ui.types.file' => 'file',
            'setono_sylius_video.ui.types.url' => 'url',
            'setono_sylius_video.ui.types.embed' => 'embed',
            'setono_sylius_video.ui.types.youtube' => 'youtube',
        ], $registry->getChoices());

        self::assertInstanceOf(FileProductVideo::class, $registry->getFactory('file')->createNew());
        self::assertInstanceOf(UrlProductVideo::class, $registry->getFactory('url')->createNew());
        self::assertInstanceOf(EmbedProductVideo::class, $registry->getFactory('embed')->createNew());
        self::assertInstanceOf(YoutubeProductVideo::class, $registry->getFactory('youtube')->createNew());
    }
}
