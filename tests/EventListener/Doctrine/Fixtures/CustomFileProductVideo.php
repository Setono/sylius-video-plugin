<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\EventListener\Doctrine\Fixtures;

use Setono\SyliusVideoPlugin\Model\FileProductVideo;

/**
 * Simulates an application that extends a concrete subtype but keeps the same discriminator value
 * (here `file`), which would shadow {@see FileProductVideo} in the STI map.
 */
final class CustomFileProductVideo extends FileProductVideo
{
    public static function getType(): string
    {
        return 'file';
    }
}
