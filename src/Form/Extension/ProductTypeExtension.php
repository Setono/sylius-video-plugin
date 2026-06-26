<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Form\Extension;

use Setono\SyliusVideoPlugin\Form\Type\ProductVideoType;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Injects the `videos` collection into the admin product form, mirroring how Sylius core injects
 * `images` via its own {@see \Sylius\Bundle\CoreBundle\Form\Extension\ProductTypeExtension}.
 */
final class ProductTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('videos', CollectionType::class, [
            'entry_type' => ProductVideoType::class,
            'entry_options' => ['label' => false],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'label' => 'setono_sylius_video.form.product.videos',
            'required' => false,
            // Required for Sylius's enhanced (JS-friendly) collection widget — the add/remove
            // buttons and the `data-form-collection` markup — mirroring how core renders images.
            'block_name' => 'entry',
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }
}
