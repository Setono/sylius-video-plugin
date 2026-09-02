<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Filesystem;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Filesystem\MediaUrlGenerator;

final class MediaUrlGeneratorTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider providePaths
     */
    public function it_joins_the_prefix_and_path_with_a_single_slash(string $prefix, string $path, string $expected): void
    {
        self::assertSame($expected, (new MediaUrlGenerator($prefix))->generate($path));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public function providePaths(): iterable
    {
        yield 'plain' => ['/media/image', 'video/ab/cd.mp4', '/media/image/video/ab/cd.mp4'];
        yield 'trailing slash on prefix' => ['/media/image/', 'video/ab/cd.mp4', '/media/image/video/ab/cd.mp4'];
        yield 'leading slash on path' => ['/media/image', '/video/ab/cd.mp4', '/media/image/video/ab/cd.mp4'];
    }
}
