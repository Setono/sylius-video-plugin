<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Functional;

use Setono\SyliusVideoPlugin\Form\Type\ProductVideoType;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Symfony\Component\Form\FormFactoryInterface;

final class ProductVideoFormTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_builds_a_new_row_with_the_shared_fields_and_every_types_fields(): void
    {
        $form = $this->service(FormFactoryInterface::class)->create(ProductVideoType::class);

        self::assertSame(['position', 'posterFile', 'type', 'file', 'url', 'html'], array_keys($form->all()));
        self::assertSame(['sylius'], $form->getConfig()->getOption('validation_groups'));
        self::assertFalse($form->get('type')->isDisabled());
    }

    /**
     * @test
     */
    public function it_builds_a_saved_row_with_only_its_own_types_field_and_a_locked_type(): void
    {
        $video = new UrlProductVideo();
        $video->setUrl('https://example.com/video');

        $form = $this->service(FormFactoryInterface::class)->create(ProductVideoType::class, $video);

        self::assertSame(['position', 'posterFile', 'type', 'url'], array_keys($form->all()));
        self::assertTrue($form->get('type')->isDisabled());
        self::assertSame('url', $form->get('type')->getData());
    }
}
