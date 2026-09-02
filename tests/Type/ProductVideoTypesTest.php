<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Type;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Tests\EventListener\Doctrine\Fixtures\CustomFileProductVideo;
use Setono\SyliusVideoPlugin\Tests\Type\Fixtures\BrokenProductVideo;
use Setono\SyliusVideoPlugin\Type\ProductVideoTypes;

final class ProductVideoTypesTest extends TestCase
{
    /**
     * @test
     */
    public function it_resolves_the_types_of_resources_whose_model_is_a_product_video(): void
    {
        $types = ProductVideoTypes::fromResources([
            // The base resource, resources without a model and unrelated models are skipped.
            'setono_sylius_video.product_video' => ['classes' => ['model' => ProductVideo::class]],
            'setono_sylius_video.file_video' => ['classes' => ['model' => FileProductVideo::class]],
            'app.unrelated' => ['classes' => ['model' => \stdClass::class]],
            'app.without_model' => ['classes' => []],
            'setono_sylius_video.url_video' => ['classes' => ['model' => UrlProductVideo::class]],
            'setono_sylius_video.embed_video' => ['classes' => ['model' => EmbedProductVideo::class]],
        ]);

        self::assertSame([
            'file' => ['alias' => 'setono_sylius_video.file_video', 'model' => FileProductVideo::class],
            'url' => ['alias' => 'setono_sylius_video.url_video', 'model' => UrlProductVideo::class],
            'embed' => ['alias' => 'setono_sylius_video.embed_video', 'model' => EmbedProductVideo::class],
        ], $types);
    }

    /**
     * @test
     */
    public function it_throws_when_two_models_resolve_to_the_same_type(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('both resolve to "file"');

        ProductVideoTypes::fromResources([
            'setono_sylius_video.file_video' => ['classes' => ['model' => FileProductVideo::class]],
            'app.custom_file_video' => ['classes' => ['model' => CustomFileProductVideo::class]],
        ]);
    }

    /**
     * @test
     */
    public function it_lets_an_error_in_a_custom_get_type_surface(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('broken');

        ProductVideoTypes::fromResources([
            'app.broken_video' => ['classes' => ['model' => BrokenProductVideo::class]],
        ]);
    }
}
