<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Kind;

/**
 * Marks a {@see \Setono\SyliusVideoPlugin\Model\ProductVideoInterface} subtype as a selectable
 * video kind in the admin form. {@see \Setono\SyliusVideoPlugin\DependencyInjection\Compiler\RegisterVideoKindsPass}
 * scans the registered Sylius resources for models carrying this attribute and builds the
 * {@see VideoKindRegistry} from them, so adding a kind needs nothing more than a subtype + a
 * resource entry — no edits to the plugin.
 *
 * The discriminator type itself is not declared here; it is read from the model's static
 * {@see \Setono\SyliusVideoPlugin\Model\ProductVideoInterface::getType()} so there is a single
 * source of truth.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsVideoKind
{
    public function __construct(
        /** Translation key for the choice label shown in the kind selector. */
        public readonly string $label,
        /** Name of the form field carrying this kind's source value (e.g. `file`/`url`/`html`). */
        public readonly string $field,
    ) {
    }
}
