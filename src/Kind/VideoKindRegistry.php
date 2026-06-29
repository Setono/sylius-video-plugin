<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Kind;

use Sylius\Component\Resource\Factory\FactoryInterface;

final class VideoKindRegistry implements VideoKindRegistryInterface
{
    /** @var array<string, array{label: string, factory: FactoryInterface<object>}> */
    private array $kinds = [];

    /**
     * @param iterable<array-key, array{type: string, label: string, factory: FactoryInterface<object>}> $kinds
     */
    public function __construct(iterable $kinds)
    {
        foreach ($kinds as $kind) {
            $this->kinds[$kind['type']] = [
                'label' => $kind['label'],
                'factory' => $kind['factory'],
            ];
        }
    }

    public function getChoices(): array
    {
        $choices = [];

        foreach ($this->kinds as $type => $kind) {
            $choices[$kind['label']] = $type;
        }

        return $choices;
    }

    public function has(string $type): bool
    {
        return isset($this->kinds[$type]);
    }

    public function getTypes(): array
    {
        return array_keys($this->kinds);
    }

    public function getFactory(string $type): FactoryInterface
    {
        if (!$this->has($type)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown video kind "%s". Registered kinds: %s.',
                $type,
                implode(', ', $this->getTypes()),
            ));
        }

        return $this->kinds[$type]['factory'];
    }
}
