<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Kind;

use Sylius\Component\Resource\Factory\FactoryInterface;

/**
 * The registered video kinds (file/url/embed and any extension kinds). It backs the `type`
 * selector and the new-row factory in the admin form; the per-kind input fields are contributed
 * separately by {@see \Setono\SyliusVideoPlugin\Form\Extension\AbstractProductVideoTypeExtension}
 * subtypes.
 */
interface VideoKindRegistryInterface
{
    /**
     * Choices for a ChoiceType: a map of translation-key label => discriminator type.
     *
     * @return array<string, string>
     */
    public function getChoices(): array;

    public function has(string $type): bool;

    /**
     * @return list<string>
     */
    public function getTypes(): array;

    /**
     * The resource factory for the kind. It is the generic Sylius factory, so it produces an
     * `object`; callers narrow the result to {@see \Setono\SyliusVideoPlugin\Model\ProductVideoInterface}.
     *
     * @return FactoryInterface<object>
     */
    public function getFactory(string $type): FactoryInterface;
}
