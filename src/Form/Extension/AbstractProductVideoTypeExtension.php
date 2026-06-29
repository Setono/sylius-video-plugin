<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Form\Extension;

use Setono\SyliusVideoPlugin\Form\Type\ProductVideoType;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Base for the per-type extensions of {@see ProductVideoType}. Each concrete extension contributes
 * the input field(s) of a single video type and is responsible only for that type, so a type can
 * own as many fields as it likes and apps can add a type by shipping their own extension — no edit
 * to the plugin's form is needed.
 *
 * A subtype only declares its discriminator type ({@see getType()}) and its field(s)
 * ({@see fields()}); the shared mechanics — reveal the field(s) for an existing row of this type,
 * carry them on a new row / the collection prototype so the client-side toggle can switch between
 * types, and strip them again on submit when another type is selected — live here and are driven
 * off that single `fields()` definition.
 */
abstract class AbstractProductVideoTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [ProductVideoType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $video = $event->getData();

            // Existing row of this type, or a new row / prototype (null) which carries every type's
            // fields so the client-side toggle has something to switch between.
            if (null === $video || ($video instanceof ProductVideoInterface && $video::getType() === $this->getType())) {
                $this->addFields($event->getForm());
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            if (!is_array($data)) {
                return;
            }

            $existing = $form->getData();
            $selectedType = $existing instanceof ProductVideoInterface
                ? $existing::getType()
                : ($data['type'] ?? null);

            if ($selectedType === $this->getType()) {
                $this->addFields($form);

                return;
            }

            // Another type is selected — drop our field(s) and their submitted values so binding
            // maps cleanly onto the chosen subtype.
            foreach (array_keys($this->fields()) as $name) {
                if ($form->has($name)) {
                    $form->remove($name);
                }

                unset($data[$name]);
            }

            $event->setData($data);
        });
    }

    /**
     * The discriminator type whose fields this extension contributes (e.g. `url`).
     */
    abstract protected function getType(): string;

    /**
     * This type's field(s), as a map of child name => [form type, options]. The single source of
     * truth for both adding the fields and stripping them when another type wins.
     *
     * @return array<string, array{class-string, array<string, mixed>}>
     */
    abstract protected function fields(): array;

    /**
     * Adds this type's field(s) to the form, idempotently (so it is safe to call from both
     * PRE_SET_DATA and PRE_SUBMIT).
     *
     * @param FormInterface<mixed> $form
     */
    private function addFields(FormInterface $form): void
    {
        foreach ($this->fields() as $name => [$type, $options]) {
            if (!$form->has($name)) {
                $form->add($name, $type, $options);
            }
        }
    }
}
