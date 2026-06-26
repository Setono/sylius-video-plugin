<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Kind;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Kind\VideoKindRegistry;
use Setono\SyliusVideoPlugin\Model\FileVideo;
use Setono\SyliusVideoPlugin\Model\UrlVideo;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class VideoKindRegistryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_exposes_choices_as_label_to_type(): void
    {
        self::assertSame([
            'setono_sylius_video.type.file' => 'file',
            'setono_sylius_video.type.url' => 'url',
        ], $this->registry()->getChoices());
    }

    /**
     * @test
     */
    public function it_resolves_field_name_model_and_types(): void
    {
        $registry = $this->registry();

        self::assertSame('file', $registry->getFieldName('file'));
        self::assertSame('url', $registry->getFieldName('url'));
        self::assertSame(FileVideo::class, $registry->getModelClass('file'));
        self::assertSame(['file', 'url'], $registry->getTypes());
        self::assertTrue($registry->has('file'));
        self::assertFalse($registry->has('embed'));
    }

    /**
     * @test
     */
    public function it_throws_for_an_unknown_kind(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->registry()->getFactory('unknown');
    }

    private function registry(): VideoKindRegistry
    {
        return new VideoKindRegistry([
            [
                'type' => 'file',
                'label' => 'setono_sylius_video.type.file',
                'field' => 'file',
                'model' => FileVideo::class,
                'factory' => $this->prophesize(FactoryInterface::class)->reveal(),
            ],
            [
                'type' => 'url',
                'label' => 'setono_sylius_video.type.url',
                'field' => 'url',
                'model' => UrlVideo::class,
                'factory' => $this->prophesize(FactoryInterface::class)->reveal(),
            ],
        ]);
    }
}
