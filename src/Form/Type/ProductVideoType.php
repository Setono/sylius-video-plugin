<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Form\Type;

use Setono\SyliusVideoPlugin\Kind\VideoKindRegistryInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Adaptive, polymorphic collection entry for a single product video.
 *
 * The shared fields (hidden `position`, optional `posterFile`) and a `type` selector are always
 * present. The kind-specific source field is wired dynamically:
 *  - PRE_SET_DATA: an existing row renders only its own field; a new row / the collection
 *    prototype renders all kind fields so the client-side toggle has something to show/hide.
 *  - PRE_SUBMIT: only the resolved kind's field is kept (the others are removed and their
 *    submitted values stripped), so binding maps onto the correct STI subclass with or without
 *    JavaScript.
 *  - empty_data: a brand-new row is instantiated as the right subtype via the kind registry's
 *    factory, keyed by the submitted `type`.
 *
 * @extends AbstractType<ProductVideoInterface>
 */
final class ProductVideoType extends AbstractType
{
    /**
     * @param class-string<ProductVideoInterface> $dataClass
     */
    public function __construct(
        private readonly VideoKindRegistryInterface $kindRegistry,
        private readonly string $dataClass,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('position', HiddenType::class, [
                'attr' => ['data-video-position' => true],
            ])
            ->add('posterFile', FileType::class, [
                'label' => 'setono_sylius_video.form.video.poster',
                'help' => 'setono_sylius_video.form.video.help.poster',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'setono_sylius_video.form.video.type',
                'help' => 'setono_sylius_video.form.video.help.type',
                'choices' => $this->kindRegistry->getChoices(),
                'mapped' => false,
                'attr' => ['data-video-type-select' => true],
                // Each option carries the name of the field its kind uses, so the client-side
                // toggle can reveal the matching input without a separate lookup table.
                'choice_attr' => fn (string $type): array => [
                    'data-video-field-target' => $this->kindRegistry->getFieldName($type),
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $video = $event->getData();

            if ($video instanceof ProductVideoInterface) {
                $this->addKindField($form, $video::getType());

                return;
            }

            // New row / collection prototype: carry every kind field so the client-side toggle
            // can reveal the one matching the chosen type.
            foreach ($this->kindRegistry->getTypes() as $type) {
                $this->addKindField($form, $type);
            }
        });

        // The `type` field is unmapped, so the data mapper resets it after PRE_SET_DATA; select
        // the existing video's type here, once mapping has run.
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $video = $event->getData();

            if ($video instanceof ProductVideoInterface) {
                $event->getForm()->get('type')->setData($video::getType());
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            if (!is_array($data)) {
                return;
            }

            $existing = $form->getData();
            $type = $existing instanceof ProductVideoInterface
                ? $existing::getType()
                : ($data['type'] ?? null);

            if (!is_string($type) || !$this->kindRegistry->has($type)) {
                return;
            }

            $keptField = $this->kindRegistry->getFieldName($type);

            foreach ($this->fieldNames() as $fieldName) {
                if ($fieldName === $keptField) {
                    continue;
                }

                if ($form->has($fieldName)) {
                    $form->remove($fieldName);
                }

                unset($data[$fieldName]);
            }

            $this->addKindField($form, $type);
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            // Validate each video's own (subtype) class-metadata constraints. The data graph is
            // only validated on the root form, so without this the per-subtype rules never fire.
            'constraints' => [new Valid()],
            'empty_data' => function (FormInterface $form): ?ProductVideoInterface {
                $type = $form->get('type')->getData();

                if (!is_string($type) || !$this->kindRegistry->has($type)) {
                    return null;
                }

                $video = $this->kindRegistry->getFactory($type)->createNew();

                return $video instanceof ProductVideoInterface ? $video : null;
            },
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_video_product_video';
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function addKindField(FormInterface $form, string $type): void
    {
        $fieldName = $this->kindRegistry->getFieldName($type);

        if ($form->has($fieldName)) {
            return;
        }

        [$formType, $extraOptions, $extraAttr] = $this->resolveField($fieldName);

        $form->add($fieldName, $formType, array_merge([
            'label' => sprintf('setono_sylius_video.form.video.%s', $fieldName),
            'help' => sprintf('setono_sylius_video.form.video.help.%s', $fieldName),
            'required' => false,
        ], $extraOptions, [
            'attr' => array_merge(['data-video-field' => $fieldName], $extraAttr),
        ]));
    }

    /**
     * @return array{class-string, array<string, mixed>, array<string, mixed>}
     */
    private function resolveField(string $fieldName): array
    {
        return match ($fieldName) {
            'file' => [FileType::class, [], []],
            'url' => [UrlType::class, ['default_protocol' => 'https'], []],
            'html' => [TextareaType::class, [], ['rows' => 4]],
            default => [TextType::class, [], []],
        };
    }

    /**
     * @return list<string>
     */
    private function fieldNames(): array
    {
        $names = [];

        foreach ($this->kindRegistry->getTypes() as $type) {
            $name = $this->kindRegistry->getFieldName($type);

            if (!in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
