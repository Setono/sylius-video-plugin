<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Type;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Type\VideoTypeRegistry;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class VideoTypeRegistryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_exposes_choices_as_label_to_type(): void
    {
        self::assertSame([
            'setono_sylius_video.ui.types.file' => 'file',
            'setono_sylius_video.ui.types.url' => 'url',
        ], $this->registry()->getChoices());
    }

    /**
     * @test
     */
    public function it_resolves_types_and_membership(): void
    {
        $registry = $this->registry();

        self::assertSame(['file', 'url'], $registry->getTypes());
        self::assertTrue($registry->has('file'));
        self::assertFalse($registry->has('embed'));
    }

    /**
     * @test
     */
    public function it_throws_for_an_unknown_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->registry()->getFactory('unknown');
    }

    private function registry(): VideoTypeRegistry
    {
        return new VideoTypeRegistry([
            [
                'type' => 'file',
                'label' => 'setono_sylius_video.ui.types.file',
                'factory' => $this->prophesize(FactoryInterface::class)->reveal(),
            ],
            [
                'type' => 'url',
                'label' => 'setono_sylius_video.ui.types.url',
                'factory' => $this->prophesize(FactoryInterface::class)->reveal(),
            ],
        ]);
    }
}
