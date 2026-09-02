<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Form\Extension;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Form\Extension\ProductTypeExtension;
use Setono\SyliusVideoPlugin\Form\Type\ProductVideoType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductTypeExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_extends_the_sylius_product_form(): void
    {
        $types = [];

        foreach (ProductTypeExtension::getExtendedTypes() as $type) {
            $types[] = $type;
        }

        self::assertSame([ProductType::class], $types);
    }

    /**
     * @test
     */
    public function it_adds_a_videos_collection_of_product_video_entries(): void
    {
        $builder = $this->prophesize(FormBuilderInterface::class);
        $builder
            ->add('videos', CollectionType::class, Argument::that(static fn (array $options): bool => ProductVideoType::class === $options['entry_type'] &&
                true === $options['allow_add'] &&
                true === $options['allow_delete'] &&
                false === $options['by_reference'] &&
                'setono_sylius_video.form.product.videos' === $options['label'] &&
                // Required for Sylius's enhanced collection widget (add/remove buttons).
                'entry' === $options['block_name']))
            ->shouldBeCalledOnce()
            ->willReturn($builder)
        ;

        (new ProductTypeExtension())->buildForm($builder->reveal(), []);
    }
}
