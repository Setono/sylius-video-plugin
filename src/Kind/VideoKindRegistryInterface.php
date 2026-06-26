<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Kind;

use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

/**
 * Declarative metadata about the registered video kinds (file/url/embed and any extension
 * kinds), consumed by the adaptive collection entry form.
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
     * `object`; callers narrow the result to {@see ProductVideoInterface}.
     *
     * @return FactoryInterface<object>
     */
    public function getFactory(string $type): FactoryInterface;

    /**
     * @return class-string<ProductVideoInterface>
     */
    public function getModelClass(string $type): string;

    /**
     * The form field name carrying the kind-specific source (e.g. `path`/`url`/`code`).
     */
    public function getFieldName(string $type): string;
}
