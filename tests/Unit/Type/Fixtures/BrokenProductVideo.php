<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Type\Fixtures;

use Setono\SyliusVideoPlugin\Model\ProductVideo;

/**
 * Simulates an application subtype whose custom getType() is broken. Such an error must surface
 * rather than make the type silently disappear.
 */
final class BrokenProductVideo extends ProductVideo
{
    public static function getType(): string
    {
        throw new \RuntimeException('broken');
    }
}
