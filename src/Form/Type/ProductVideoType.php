<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Form\Type;

use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Type\VideoTypeRegistryInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Polymorphic collection entry for a single product video.
 *
 * This type only carries the fields shared by every type — `position`, the optional `posterFile`
 * and the `type` selector — plus the factory that instantiates the right subtype for a new row
 * (via `empty_data`, keyed by the submitted `type`). The type-specific input fields (url/file/html
 * and any extension type's fields) are contributed by per-type ProductVideoType extensions
 * ({@see \Setono\SyliusVideoPlugin\Form\Extension\AbstractProductVideoTypeExtension}), so a new
 * type needs no edits here.
 */
final class ProductVideoType extends AbstractResourceType
{
    /**
     * @param class-string<ProductVideoInterface> $dataClass
     */
    public function __construct(
        string $dataClass,
        private readonly VideoTypeRegistryInterface $typeRegistry,
    ) {
        parent::__construct($dataClass);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('position', IntegerType::class, [
                'label' => 'setono_sylius_video.form.video.position',
                'help' => 'setono_sylius_video.form.video.help.position',
                'required' => false,
            ])
            ->add('posterFile', FileType::class, [
                'label' => 'setono_sylius_video.form.video.poster',
                'help' => 'setono_sylius_video.form.video.help.poster',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'setono_sylius_video.form.video.type',
                'help' => 'setono_sylius_video.form.video.help.type',
                'choices' => $this->typeRegistry->getChoices(),
                'mapped' => false,
                // The selected value is the discriminator type; the client-side toggle reveals the
                // matching type's fields (each tagged `data-video-fields="<type>"`).
                'attr' => ['data-video-type-select' => true],
            ])
        ;

        // The `type` field is unmapped, so the data mapper resets it after the data is set; select
        // the existing video's type here, once mapping has run.
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $video = $event->getData();

            if ($video instanceof ProductVideoInterface) {
                $event->getForm()->get('type')->setData($video::getType());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            // Validate each video's own (subtype) class-metadata constraints. The data graph is
            // only validated on the root form, so without this the per-subtype rules never fire.
            'constraints' => [new Valid()],
            'empty_data' => function (FormInterface $form): ?ProductVideoInterface {
                $type = $form->get('type')->getData();

                if (!is_string($type) || !$this->typeRegistry->has($type)) {
                    return null;
                }

                $video = $this->typeRegistry->getFactory($type)->createNew();

                return $video instanceof ProductVideoInterface ? $video : null;
            },
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_video_product_video';
    }
}
