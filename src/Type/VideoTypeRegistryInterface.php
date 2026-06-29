<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Type;

use Sylius\Resource\Factory\FactoryInterface;

/**
 * The registered video types (file/url/embed and any extension types). It backs the `type`
 * selector and the new-row factory in the admin form; the per-type input fields are contributed
 * separately by {@see \Setono\SyliusVideoPlugin\Form\Extension\AbstractProductVideoTypeExtension}
 * subtypes.
 */
interface VideoTypeRegistryInterface
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
     * The resource factory for the type. It produces an `object`; callers narrow the result to
     * {@see \Setono\SyliusVideoPlugin\Model\ProductVideoInterface}.
     *
     * @return FactoryInterface<object>
     */
    public function getFactory(string $type): FactoryInterface;
}
