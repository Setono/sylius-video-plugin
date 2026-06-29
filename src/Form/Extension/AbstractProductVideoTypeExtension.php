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
 * The shared mechanics (reveal the field(s) for an existing row of this type, carry them on a new
 * row / the collection prototype so the client-side toggle can switch between types, and strip
 * them again on submit when another type is selected) live here; subclasses only declare their
 * type and build their field(s).
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
            foreach ($this->fieldNames() as $name) {
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
     * The names of the field(s) this extension adds, used to strip them when another type wins.
     *
     * @return list<string>
     */
    abstract protected function fieldNames(): array;

    /**
     * Adds this type's field(s) to the form. Must be idempotent (guard each child with `has()`),
     * as it can be called from both PRE_SET_DATA and PRE_SUBMIT.
     *
     * @param FormInterface<mixed> $form
     */
    abstract protected function addFields(FormInterface $form): void;
}
