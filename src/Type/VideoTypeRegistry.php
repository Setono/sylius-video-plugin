<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Type;

use Sylius\Resource\Factory\FactoryInterface;

final class VideoTypeRegistry implements VideoTypeRegistryInterface
{
    /** @var array<string, array{label: string, factory: FactoryInterface<object>}> */
    private array $types = [];

    /**
     * @param iterable<array-key, array{type: string, label: string, factory: FactoryInterface<object>}> $types
     */
    public function __construct(iterable $types)
    {
        foreach ($types as $type) {
            $this->types[$type['type']] = [
                'label' => $type['label'],
                'factory' => $type['factory'],
            ];
        }
    }

    public function getChoices(): array
    {
        $choices = [];

        foreach ($this->types as $type => $config) {
            $choices[$config['label']] = $type;
        }

        return $choices;
    }

    public function has(string $type): bool
    {
        return isset($this->types[$type]);
    }

    public function getTypes(): array
    {
        return array_keys($this->types);
    }

    /**
     * @return FactoryInterface<object>
     */
    public function getFactory(string $type): FactoryInterface
    {
        if (!$this->has($type)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown video type "%s". Registered types: %s.',
                $type,
                implode(', ', $this->getTypes()),
            ));
        }

        return $this->types[$type]['factory'];
    }
}
