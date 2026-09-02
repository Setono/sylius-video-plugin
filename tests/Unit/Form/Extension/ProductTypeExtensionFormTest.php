<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Form\Extension;

use Setono\SyliusVideoPlugin\Form\Extension\EmbedProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Form\Extension\FileProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Form\Extension\ProductTypeExtension;
use Setono\SyliusVideoPlugin\Form\Extension\UrlProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Form\Type\ProductVideoType;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Tests\Unit\Form\Extension\Fixtures\VideosOwner;
use Setono\SyliusVideoPlugin\Type\VideoTypeRegistry;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

/**
 * Exercises the `videos` collection the extension adds, bound to an owner with an adder and
 * remover, the way the Sylius product form maps it back.
 */
final class ProductTypeExtensionFormTest extends TypeTestCase
{
    /**
     * @test
     */
    public function it_adds_a_new_video_to_the_owner(): void
    {
        $owner = new VideosOwner();
        $form = $this->ownerForm($owner);

        $form->submit(['videos' => [['type' => 'url', 'url' => 'https://example.com/video']]]);

        self::assertTrue($form->isValid());
        self::assertCount(1, $owner->getVideos());
        self::assertInstanceOf(UrlProductVideo::class, $owner->getVideos()->first());
    }

    /**
     * @test
     */
    public function it_rejects_an_unknown_type_without_breaking_the_owner(): void
    {
        $owner = new VideosOwner();
        $form = $this->ownerForm($owner);

        $form->submit(['videos' => [['type' => 'does-not-exist', 'url' => 'https://example.com/video']]]);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('videos')->get('0')->get('type')->getErrors());
        self::assertCount(0, $owner->getVideos());
    }

    /**
     * @return FormInterface<mixed>
     */
    private function ownerForm(VideosOwner $owner): FormInterface
    {
        $builder = $this->factory->createBuilder(FormType::class, $owner, ['data_class' => VideosOwner::class]);
        (new ProductTypeExtension())->buildForm($builder, []);

        return $builder->getForm();
    }

    /**
     * @return list<\Symfony\Component\Form\FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $registry = new VideoTypeRegistry([
            ['type' => 'file', 'label' => 'setono_sylius_video.ui.types.file', 'factory' => new Factory(FileProductVideo::class)],
            ['type' => 'url', 'label' => 'setono_sylius_video.ui.types.url', 'factory' => new Factory(UrlProductVideo::class)],
            ['type' => 'embed', 'label' => 'setono_sylius_video.ui.types.embed', 'factory' => new Factory(EmbedProductVideo::class)],
        ]);

        return [
            new PreloadedExtension(
                [new ProductVideoType(ProductVideo::class, ['sylius'], $registry)],
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
