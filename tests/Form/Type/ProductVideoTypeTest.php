<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Form\Type;

use Setono\SyliusVideoPlugin\Form\Extension\EmbedProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Form\Extension\FileProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Form\Extension\UrlProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Form\Type\ProductVideoType;
use Setono\SyliusVideoPlugin\Kind\VideoKindRegistry;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class ProductVideoTypeTest extends TypeTestCase
{
    /**
     * @test
     */
    public function it_instantiates_a_url_video_subtype_on_submit(): void
    {
        $form = $this->factory->create(ProductVideoType::class);

        $form->submit([
            'type' => 'url',
            'url' => 'https://example.com/video',
            'position' => '2',
        ]);

        self::assertTrue($form->isSynchronized());

        $data = $form->getData();
        self::assertInstanceOf(UrlProductVideo::class, $data);
        self::assertSame('https://example.com/video', $data->getUrl());
        self::assertSame(2, $data->getPosition());
    }

    /**
     * @test
     */
    public function it_instantiates_an_embed_video_subtype_on_submit(): void
    {
        $form = $this->factory->create(ProductVideoType::class);

        $form->submit([
            'type' => 'embed',
            'html' => '<iframe></iframe>',
        ]);

        self::assertTrue($form->isSynchronized());

        $data = $form->getData();
        self::assertInstanceOf(EmbedProductVideo::class, $data);
        self::assertSame('<iframe></iframe>', $data->getHtml());
    }

    /**
     * @test
     */
    public function it_shows_the_kind_field_and_selected_type_for_an_existing_video(): void
    {
        $video = new UrlProductVideo();
        $video->setUrl('https://example.com/video');

        $form = $this->factory->create(ProductVideoType::class, $video);

        self::assertTrue($form->has('url'));
        self::assertFalse($form->has('html'));
        self::assertSame('https://example.com/video', $form->get('url')->getData());
        self::assertSame('url', $form->get('type')->getData());
    }

    /**
     * @test
     */
    public function it_keeps_an_existing_videos_subtype_even_if_another_type_is_submitted(): void
    {
        $video = new UrlProductVideo();
        $video->setUrl('https://example.com/old');

        $form = $this->factory->create(ProductVideoType::class, $video);

        // The user tampered with the (effectively read-only) type select; the row must remain a
        // UrlProductVideo and only its own column may change.
        $form->submit([
            'type' => 'embed',
            'url' => 'https://example.com/new',
            'html' => '<iframe></iframe>',
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertInstanceOf(UrlProductVideo::class, $form->getData());
        self::assertSame('https://example.com/new', $form->getData()->getUrl());
    }

    /**
     * @test
     */
    public function it_ignores_an_unknown_submitted_type_for_a_new_row(): void
    {
        $form = $this->factory->create(ProductVideoType::class);

        $form->submit(['type' => 'does-not-exist']);

        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }

    /**
     * @test
     */
    public function it_configures_help_text_on_the_fields(): void
    {
        // A new row carries every kind field, so all of them are present here.
        $form = $this->factory->create(ProductVideoType::class);

        self::assertSame('setono_sylius_video.form.video.help.type', $form->get('type')->getConfig()->getOption('help'));
        self::assertSame('setono_sylius_video.form.video.help.poster', $form->get('posterFile')->getConfig()->getOption('help'));
        self::assertSame('setono_sylius_video.form.video.help.file', $form->get('file')->getConfig()->getOption('help'));
        self::assertSame('setono_sylius_video.form.video.help.url', $form->get('url')->getConfig()->getOption('help'));
        self::assertSame('setono_sylius_video.form.video.help.html', $form->get('html')->getConfig()->getOption('help'));
    }

    /**
     * @return list<\Symfony\Component\Form\FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $registry = new VideoKindRegistry([
            ['type' => 'file', 'label' => 'setono_sylius_video.ui.types.file', 'factory' => new Factory(FileProductVideo::class)],
            ['type' => 'url', 'label' => 'setono_sylius_video.ui.types.url', 'factory' => new Factory(UrlProductVideo::class)],
            ['type' => 'embed', 'label' => 'setono_sylius_video.ui.types.embed', 'factory' => new Factory(EmbedProductVideo::class)],
        ]);

        return [
            new PreloadedExtension(
                [new ProductVideoType($registry, ProductVideo::class)],
                [ProductVideoType::class => [
                    new FileProductVideoTypeExtension(),
                    new UrlProductVideoTypeExtension(),
                    new EmbedProductVideoTypeExtension(),
                ]],
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
